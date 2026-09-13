/**
 * Chained dropdowns for course -> sub-course -> subject.
 *
 * Supported on any form with a [data-chained] attribute containing selects
 * named course_id, subcourse_id and subject_id. If the server renders a
 * preselected value, set data-value="<id>" on the corresponding select —
 * it will be restored once the data is fetched on page load.
 */
(function () {
    'use strict';

    var BASE = document.body.getAttribute('data-base') || '';

    function urlFor(action, id) {
        return BASE + 'ajax.php?action=' + action + '&id=' + encodeURIComponent(id) + '&t=' + Date.now();
    }

    function placeholder(text) {
        return '<option value="">' + text + '</option>';
    }

    function fillSelect(select, data, selectedValue) {
        var list = data || [];
        select.innerHTML = '<option value="">-- Select --</option>';
        list.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            if (String(item.id) === String(selectedValue || '')) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    function loadSubcourses(group) {
        var course = group.elements['course_id'];
        var sub = group.elements['subcourse_id'];
        var subject = group.elements['subject_id'];

        if (!course.value) {
            sub.innerHTML = placeholder('-- Select sub-course --');
            subject.innerHTML = placeholder('-- Select subject --');
            return;
        }
        var preselected = sub.dataset.value || '';
        sub.dataset.value = '';
        sub.innerHTML = placeholder('Loading...');
        fetch(urlFor('subcourses', course.value))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                fillSelect(sub, d.data || [], preselected);
                if (sub.value) { loadSubjects(group); }
            })
            .catch(function () { sub.innerHTML = placeholder('Unable to load options'); });
    }

    function loadSubjects(group) {
        var sub = group.elements['subcourse_id'];
        var subject = group.elements['subject_id'];

        if (!sub.value) {
            subject.innerHTML = placeholder('-- Select subject --');
            return;
        }
        var preselected = subject.dataset.value || '';
        subject.dataset.value = '';
        subject.innerHTML = placeholder('Loading...');
        fetch(urlFor('subjects', sub.value))
            .then(function (r) { return r.json(); })
            .then(function (d) { fillSelect(subject, d.data || [], preselected); })
            .catch(function () { subject.innerHTML = placeholder('Unable to load options'); });
    }

    function wire(group) {
        group.elements['course_id'].addEventListener('change', function () { loadSubcourses(group); });
        group.elements['subcourse_id'].addEventListener('change', function () { loadSubjects(group); });
        if (group.elements['course_id'].value || group.elements['subcourse_id'].dataset.value) {
            loadSubcourses(group);
        }
    }

    document.querySelectorAll('form[data-chained]').forEach(wire);
})();