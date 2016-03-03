window.addEventListener('load', function () {
    var dragSrcEl = null,
        requiredText = document.querySelector('#requiredTranslation').value,
        farb = jQuery.farbtastic('#colorPicker'),
        elements = document.getElementsByClassName('js-n2go-widget-field'),
        i,
        renderHTML = function () {
            var widget = document.getElementById('widgetSourceCode'),
                loaderMask = document.getElementById('previewLoadingMask'),
                view = document.getElementById('widgetPreview');
            widget.style.display = 'none';

            loaderMask.style.display = 'block';
            view.addEventListener('load', function () {
                view.contentWindow.document.body.innerHTML = widget.value;
                view.style.display = 'block';
                loaderMask.style.display = 'none';
            });

            view.src = 'index.php';

            document.getElementById('btnShowPreview').className = 'button active';
            document.getElementById('btnShowSource').className = 'button';
        };

    function buildWidgetForm(rebuildForm) {
        var sourceCode = '',
            tempName;
        if (rebuildForm) {
            var checkBoxes = document.querySelectorAll('input[type="checkbox"]'),
                fields = [], i, elem,
                texts, styles, inputStyle = '';

            for (i = 0; i < checkBoxes.length; i++) {
                if (checkBoxes[i].checked === true) {
                    tempName = 'fields[' + checkBoxes[i].value + ']';
                    elem = [];
                    elem['sort'] = document.getElementsByName(tempName + '[sort]')[0].value;
                    elem['required'] = document.getElementsByName(tempName + '[required]')[0].value ? 'required' : '';
                    elem['name'] = document.getElementsByName(tempName + '[title]')[0].value;
                    elem['id'] = checkBoxes[i].value;

                    fields.push(elem);
                }
            }

            texts = [];
            texts['button'] = document.getElementById('buttonText').value;

            styles = [];
            styles['textColor'] = document.getElementById('textFieldColor').value;
            styles['formBgColor'] = document.getElementById('formBackgroundColor').value;
            styles['borderColor'] = document.getElementById('inputBorderColor').value;
            styles['backgroundColor'] = document.getElementById('inputBackgroundColor').value;
            styles['btnTextColor'] = document.getElementById('buttonTextColor').value;
            styles['btnBackgroundColor'] = document.getElementById('buttonBackgroundColor').value;

            fields.sort(function (a, b) {
                return a['sort'] - b['sort'];
            });

            sourceCode = '<div id="n2goResponseArea" style="';
            sourceCode += styles['textColor'] ? 'color:' + styles['textColor'] + '; ' : '';
            sourceCode += styles['formBgColor'] ? ' background-color:' + styles['formBgColor'] + '; ' : '';
            sourceCode += '">';
            sourceCode += '\n  <form method="post" id="n2goForm">';

            if (styles['borderColor'] || styles['backgroundColor'] || styles['textColor']) {
                inputStyle = 'style="';
                inputStyle += styles['borderColor'] ? 'border-color:' + styles['borderColor'] + '; ' : '';
                inputStyle += styles['backgroundColor'] ? 'background-color:' + styles['backgroundColor'] + '; ' : '';
                inputStyle += styles['textColor'] ? 'color:' + styles['textColor'] + '; ' : '';
                inputStyle += '" ';
            }

            for (i = 0; i < fields.length; i++) {
                if (fields[i]['name'] === 'Gender') {
                    sourceCode += '\n    ' + fields[i]['name'] + '<br />\n    ' + '<select ' + inputStyle + 'name="' + fields[i]['id'] + '" ' + fields[i]['required'] + '>';
                    sourceCode += '\n      <option value=" "></option>';
                    sourceCode += '\n      <option value="m">Male</option>';
                    sourceCode += '\n      <option value="f">Female</option>';
                    sourceCode += '\n    </select><br>';
                } else {
                    sourceCode += '\n    ' + fields[i]['name'] + '<br />\n    ' + '<input ' + inputStyle + 'type="text" name="' + fields[i]['id'] + '" ' + fields[i]['required'] + ' /><br />';
                }
            }

            sourceCode += '\n    <br />\n    <div class="message"></div>';
            sourceCode += '\n    <input ';
            if (styles['btnTextColor'] || styles['btnBackgroundColor']) {
                sourceCode += 'style="';
                sourceCode += styles['btnTextColor'] ? 'color:' + styles['btnTextColor'] + ';' : '';
                sourceCode += styles['btnBackgroundColor'] ? 'background-color:' + styles['btnBackgroundColor'] + ';' : '';
                sourceCode += '"';
            }

            sourceCode += ' id="n2goButton" type="button" value="' + texts['button'] + '" class="form-submit" />\n  </form>\n</div>';
            document.getElementById('widgetSourceCode').innerHTML = sourceCode;
            document.getElementById('widgetSourceCode').value = sourceCode;
        }

        renderHTML();
    }

    function extractValues(elem) {
        return {
            valueRequired: elem.children[4].value,
            id: elem.children[0].children[0].id,
            class: elem.children[0].children[0].className,
            title: elem.children[5].value,
            checked: elem.children[0].children[0].checked,
            disabled: elem.children[0].children[0].disabled,
            label: elem.children[2].innerHTML
        };
    }

    function importValues(elem, values) {
        var field = 'fields[' + values.id + ']';
        elem.children[1].name = field + '[sort]';
        elem.children[4].name = field + '[required]';
        elem.children[4].value = values.valueRequired;
        elem.children[0].children[0].id = values.id;
        elem.children[0].children[0].className = values.class;
        elem.children[0].children[0].name = field + '[selected]';
        elem.children[0].htmlFor = values.id;
        elem.children[2].htmlFor = values.id;
        elem.children[0].children[0].value = values.id;
        elem.children[0].children[0].title = values.title;
        elem.children[5].value = values.title;
        elem.children[5].name = field + '[title]';
        elem.children[3].innerHTML = values.title;
        elem.children[2].innerHTML = values.label;
        elem.children[0].children[0].checked = values.checked;
        elem.children[0].children[0].disabled = values.disabled;
    }

    function handleDragStart(e) {
        dragSrcEl = this;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('Text', JSON.stringify(extractValues(this)));
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        return false;
    }

    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('over');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        this.classList.remove('over');
    }

    function handleDrop(e) {
        e.stopPropagation();
        e.preventDefault();

        if (dragSrcEl !== this) {
            var a = JSON.parse(e.dataTransfer.getData('Text'));
            var b = extractValues(this);
            importValues(dragSrcEl, b);
            importValues(this, a);
        }

        return false;
    }

    function handleDragEnd() {
        [].forEach.call(document.querySelectorAll('#widgetFields .widgetField'), function (field) {
            field.classList.remove('over');
        });

        buildWidgetForm(true);
    }

    [].forEach.call(document.querySelectorAll('#widgetFields .widgetField'), function (field) {
        field.addEventListener('dragstart', handleDragStart, false);
        field.addEventListener('dragenter', handleDragEnter, false);
        field.addEventListener('dragover', handleDragOver, false);
        field.addEventListener('dragleave', handleDragLeave, false);
        field.addEventListener('drop', handleDrop, false);
        field.addEventListener('dragend', handleDragEnd, false);
    });

    buildWidgetForm(true);

    document.getElementById('btnShowSource').onclick = function () {
        var view = document.getElementById('widgetSourceCode');
        view.style.display = 'block';
        document.getElementById('widgetPreview').style.display = 'none';
        this.className = 'button active';
        document.getElementById('btnShowPreview').className = 'button';
    };

    document.getElementById('btnShowPreview').onclick = function () {
        renderHTML();
    };

    function transformToEditBox() {
        var me = this,
            textField = document.createElement('input'),
            oldText = me.innerHTML.replace(requiredText, '').trim();

        textField.value = oldText;
        textField.addEventListener('blur', function(){
            var val = this.value,
                required = this.parentElement.children[4].value;

            this.parentElement.draggable = true;
            val = val ? val : oldText;
            if (oldText === val) {
                this.parentNode.replaceChild(me, this);
                return true;
            }

            this.parentElement.children[5].value = val;
            me.innerHTML = val + (required ? requiredText : '');

            this.parentNode.replaceChild(me, this);
            buildWidgetForm(true);
        }, false);

        me.parentNode.replaceChild(textField, me);
        textField.parentElement.draggable = false;
        textField.focus();
    }

    [].forEach.call(document.querySelectorAll('.n2go-editable-label'), function (field) {
        field.addEventListener('click', transformToEditBox, false);
    });

    function hookClickHandler(checkbox) {
        checkbox.onclick = function (e) {
            var uncles = this.parentElement.parentElement.children,
                hiddenReq = uncles[4];

            if (!this.checked) {
                if (hiddenReq.value === '1') {
                    e.preventDefault();
                    this.checked = true;
                    hiddenReq.value = '';
                    this.className = 'js-n2go-widget-field';
                    uncles[3].innerHTML = uncles[5].value;
                    buildWidgetForm(true);

                    return false;
                }
            } else {
                hiddenReq.value = '1';
                this.className += ' required';
                uncles[3].innerHTML += requiredText;
            }
        };
    }

    for (i = 0; i < elements.length; i++) {
        if (elements[i].type === 'checkbox') {
            hookClickHandler(elements[i]);
        }

        elements[i].onchange = function () {
            buildWidgetForm(true);
        };
    }

    jQuery('.color-picker').focus(function () {
        var input = this;

        // reset to start position before linking to current input
        farb.linkTo(function () {
        }).setColor('#000');
        farb.linkTo(function (color) {
            input.style.backgroundColor = color;
            input.style.color = farb.RGBToHSL(farb.unpack(color))[2] > 0.5 ? '#000' : '#fff';
            input.value = color;
        }).setColor(input.value);
    }).blur(function () {
        farb.linkTo(function () {
        }).setColor('#000');
        if (!this.value) {
            this.style.backgroundColor = '';
            this.style.color = '';
        }

        buildWidgetForm(true);
    });
});