<script>
(function() {
    const categorySelect = document.getElementById('rule-category');
    const sections = document.querySelectorAll('.rule-category-fields');

    function showCategory(category) {
        sections.forEach(function(section) {
            section.style.display = section.dataset.category === category ? '' : 'none';
        });
    }

    if (categorySelect) {
        showCategory(categorySelect.value);
        categorySelect.addEventListener('change', function() {
            showCategory(this.value);
        });
    }

    // Labour Type only makes sense when "Labour" is checked under Employee Type.
    const labourCheckbox = document.getElementById('etype_labour');
    const labourWrapper = document.getElementById('labour-type-wrapper');
    function toggleLabourWrapper() {
        if (!labourCheckbox || !labourWrapper) return;
        labourWrapper.style.display = labourCheckbox.checked ? '' : 'none';
    }
    document.querySelectorAll('.rule-employee-type').forEach(function(cb) {
        cb.addEventListener('change', toggleLabourWrapper);
    });
    toggleLabourWrapper();

    // Fixed Payroll Days only applies when LOP Calculation Basis = Fixed Days.
    const lopBasis = document.getElementById('lop-calculation-basis');
    const fixedDaysWrapper = document.getElementById('fixed-payroll-days-wrapper');
    function toggleFixedDays() {
        if (!lopBasis || !fixedDaysWrapper) return;
        fixedDaysWrapper.style.display = lopBasis.value === 'fixed_days' ? '' : 'none';
    }
    if (lopBasis) {
        lopBasis.addEventListener('change', toggleFixedDays);
        toggleFixedDays();
    }

    // Sample Employee Number preview (AJAX, no sequence consumed).
    const previewBtn = document.getElementById('preview-employee-number-btn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            const form = document.getElementById('rule-form');
            const payload = {
                employee_category: form.employee_category.value,
                branch_id: form.branch_id.value,
                contractor_id: form.contractor_id.value,
                prefix: form.prefix.value,
                include_branch_code: form.include_branch_code.checked ? 1 : 0,
                include_contractor_code: form.include_contractor_code.checked ? 1 : 0,
                separator: form.separator.value,
                sequence_start: form.sequence_start.value,
                sequence_length: form.sequence_length.value,
                include_financial_year: form.include_financial_year.checked ? 1 : 0,
                include_calendar_year: form.include_calendar_year.checked ? 1 : 0,
                reset_frequency: form.reset_frequency.value,
            };

            fetch('{{ route('rule-engine.preview-employee-number') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    document.getElementById('sample-employee-number').value = data.sample || '';
                })
                .catch(function() {
                    document.getElementById('sample-employee-number').value = 'Unable to preview';
                });
        });
    }
})();
</script>
