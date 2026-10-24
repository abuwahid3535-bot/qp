// paper-files.js
// Stores question paper PDFs in Firestore as small base64 chunks.
// This keeps the portal on the free Spark plan, where Firebase Storage
// is not available for new projects.

import { db } from './firebase-config.js';
import {
    collection,
    deleteDoc,
    doc,
    getDocs,
    query,
    setDoc,
    where
} from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js';

const FIRESTORE_PREFIX = 'firestore://';
const CHUNK_SIZE = 384 * 1024; // 384 KiB binary -> about 512 KiB base64

export function isFirestoreFile(paperOrUrl) {
    const url = typeof paperOrUrl === 'string' ? paperOrUrl : paperOrUrl?.fileUrl;
    return typeof url === 'string' && url.startsWith(FIRESTORE_PREFIX);
}

export function paperIdFromUrl(fileUrl) {
    return isFirestoreFile(fileUrl) ? fileUrl.slice(FIRESTORE_PREFIX.length) : '';
}

function readBlobAsBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const result = String(reader.result || '');
            const comma = result.indexOf(',');
            resolve(comma === -1 ? '' : result.slice(comma + 1));
        };
        reader.onerror = () => reject(reader.error || new Error('Could not read the PDF file.'));
        reader.readAsDataURL(blob);
    });
}

async function deleteChunks(chunkRefs) {
    for (const chunkRef of chunkRefs) {
        try {
            await deleteDoc(chunkRef);
        } catch (err) {
            console.warn('Could not clean up a PDF chunk:', err);
        }
    }
}

export async function savePaperFile(file, paperId, onProgress) {
    if (!file || !paperId) throw new Error('Missing file or paper ID.');

    const totalChunks = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));
    const mimeType = file.type || 'application/pdf';
    const chunkRefs = [];

    try {
        for (let index = 0; index < totalChunks; index++) {
            const start = index * CHUNK_SIZE;
            const end = Math.min(file.size, start + CHUNK_SIZE);
            const data = await readBlobAsBase64(file.slice(start, end));

            if (!data) throw new Error('Could not read the PDF file.');

            const chunkRef = doc(db, 'paper_files', `${paperId}_${index}`);
            await setDoc(chunkRef, {
                paperId,
                index,
                totalChunks,
                fileName: file.name,
                mimeType,
                data
            });

            chunkRefs.push(chunkRef);

            if (typeof onProgress === 'function') {
                const percent = Math.round(((index + 1) / totalChunks) * 100);
                onProgress(percent, index + 1, totalChunks);
            }
        }

        return { totalChunks };
    } catch (err) {
        await deleteChunks(chunkRefs);
        throw err;
    }
}

export async function findPaperChunks(paperId) {
    const snap = await getDocs(
        query(collection(db, 'paper_files'), where('paperId', '==', paperId))
    );

    return snap.docs
        .map((chunk) => ({ id: chunk.id, ...chunk.data() }))
        .sort((a, b) => Number(a.index) - Number(b.index));
}

export async function deletePaperFile(paperOrUrl) {
    if (!isFirestoreFile(paperOrUrl)) return;

    const paperId = paperIdFromUrl(
        typeof paperOrUrl === 'string' ? paperOrUrl : paperOrUrl.fileUrl
    );

    const chunks = await findPaperChunks(paperId);
    for (const chunk of chunks) {
        try {
            await deleteDoc(doc(db, 'paper_files', chunk.id));
        } catch (err) {
            console.warn('Could not delete a PDF chunk:', err);
        }
    }
}

export async function getPaperBlob(paper) {
    if (!paper || !paper.fileUrl) {
        throw new Error('The PDF file is not available for this paper.');
    }

    if (!isFirestoreFile(paper)) {
        const res = await fetch(paper.fileUrl);
        if (!res.ok) {
            throw new Error('Could not download the PDF file.');
        }

        return {
            blob: await res.blob(),
            fileName: paper.fileName || 'question-paper.pdf',
            mimeType: res.headers.get('content-type') || 'application/pdf'
        };
    }

    const paperId = paperIdFromUrl(paper.fileUrl);
    const chunks = await findPaperChunks(paperId);

    if (!chunks.length) {
        throw new Error('The PDF file is missing from the server.');
    }

    const expectedChunks = Number(chunks[0].totalChunks) || chunks.length;
    if (chunks.length !== expectedChunks) {
        throw new Error('The PDF file is incomplete. Please ask the uploader to upload it again.');
    }

    const byteArrays = [];
    let totalLength = 0;

    for (const chunk of chunks) {
        let binary;
        try {
            binary = atob(chunk.data || '');
        } catch {
            throw new Error('The PDF file is damaged. Please ask the uploader to upload it again.');
        }

        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }

        byteArrays.push(bytes);
        totalLength += bytes.length;
    }

    const combined = new Uint8Array(totalLength);
    let offset = 0;

    for (const bytes of byteArrays) {
        combined.set(bytes, offset);
        offset += bytes.length;
    }

    return {
        blob: new Blob([combined], {
            type: chunks[0].mimeType || 'application/pdf'
        }),
        fileName: paper.fileName || chunks[0].fileName || 'question-paper.pdf',
        mimeType: chunks[0].mimeType || 'application/pdf'
    };
}

export async function downloadPaperFile(paper) {
    const { blob, fileName } = await getPaperBlob(paper);
    const objectUrl = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = fileName || paper.fileName || 'question-paper.pdf';
    document.body.appendChild(link);
    link.click();
    link.remove();

    setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    return fileName;
}

export async function openPaperFileInNewTab(paper) {
    const win = window.open('about:blank', '_blank');

    if (!win) {
        throw new Error('Your browser blocked the new tab. Please allow pop-ups for this site.');
    }

    win.opener = null;
    win.document.write(
        '<p style="font-family: sans-serif; padding: 2rem;">Loading PDF...</p>'
    );

    try {
        const { blob } = await getPaperBlob(paper);
        const objectUrl = URL.createObjectURL(blob);
        win.location.replace(objectUrl);
        return objectUrl;
    } catch (err) {
        win.close();
        throw err;
    }
}
