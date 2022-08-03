const donationFilters = () => {

    const fields = {
        range: "range",
        exact: "exact-date",
        fullTime: 'full-time',
        month: 'month',
        year: 'filter-year',
        type: 'type'
    }

    const names = {
        from: 'from',
        to: 'to',
        ...fields
    }

    const ID = id => document.getElementById(id);
    const contentSelector = ID('donation-filter-content-select');
    const formSubmiter = ID('donation-filter-form-submit');
    const form = ID('donation-filter-form');
    const resetFilters = ID('donation-filter-form-reset');

    const resetElements = (missType) => {
        document.querySelectorAll('.donation-filter-item').forEach(el => {
            if (el.classList.contains(fields.type) && missType) {
                console.log('here')
                return;
            }
            const select = el.querySelector('select');
            if (select) { select.value = null};

            (el.querySelectorAll('input') ?? []).forEach((el) => {
                el.value = null
            })
        })
        submitForm(); // reset data table
    }

    const changeContent = className => {
        resetElements(true);
        document.querySelectorAll('.donation-filter-item').forEach(el => {
            if (el.classList.contains(fields.type)) {
                return
            }
            if (el.classList.contains(className)) {
                el.classList.add("filter-visible");
                el.classList.remove('filter-hidden')
            } else {
                el.classList.remove('filter-visible')
                el.classList.add('filter-hidden')
            }
        })
    }

    contentSelector.onchange = e => changeContent(e.target.value);

    const filterForm = (formData, ...keys) => {
        const newFormData = new FormData()
        for (let key of formData.keys()) {
            if ([...keys].includes(key)) {
                const value = formData.get(key);
                if (value) {
                    newFormData.set(key, formData.get(key))
                }
            }
        }
        const donationType = formData.get(fields.type);

        if (donationType) {
            newFormData.set(fields.type, donationType)
        }
        return newFormData
    }

    const submitForm = () => {
        let formData = new FormData(form);
        const { value } = contentSelector;

        if (value === fields.range) {
            formData = filterForm(formData, names.from, names.to)
        } else {
            formData = filterForm(formData, value)
        }

        let tableContainer = document.getElementById('table-data');
        tableContainer.classList.add('load');

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "/ajax");
        xhr.onreadystatechange = function() {
            if (this.readyState != 4) return;

            tableContainer.innerHTML = this.responseText;
            tableContainer.classList.remove('load');
        }
        xhr.send(formData);
    }

    formSubmiter.addEventListener('click', submitForm);
    resetFilters.addEventListener('click', () => resetElements())

};

document.addEventListener('DOMContentLoaded', donationFilters)

var tableToExcel = (function() {
    var uri = 'data:application/vnd.ms-excel;base64,'
        , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body><table>{table}</table></body></html>'
        , base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
        , format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
    return function(table, name) {
        if (!table.nodeType) table = document.getElementById(table)
        var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML}
        window.location.href = uri + base64(format(template, ctx))
    }
})()
