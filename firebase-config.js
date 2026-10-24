// firebase-config.js
// Firebase configuration for QP Portal

import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js';
import { getAuth } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';
import { getFirestore } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js';
import { getStorage } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-storage.js';

const firebaseConfig = {
  apiKey: "AIzaSyCB3hhzV_WMjFl-bz6lIBZ84fJh9uvX_Js",
  authDomain: "qp-portal-1c92c.firebaseapp.com",
  projectId: "qp-portal-1c92c",
  storageBucket: "qp-portal-1c92c.firebasestorage.app",
  messagingSenderId: "877659085768",
  appId: "1:877659085768:web:2747d17b91289d7573fefd"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Export services for use in other modules
export const auth = getAuth(app);
export const db = getFirestore(app);
export const storage = getStorage(app);