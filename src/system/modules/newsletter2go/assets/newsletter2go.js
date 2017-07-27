window.addEventListener('load', function () {
    var formUniqueCode = document.getElementById('formUniqueCode') ? document.getElementById('formUniqueCode').value.trim() : null,
        widgetPreviewSubscribe = document.getElementById('widgetPreviewSubscribe') ? document.getElementById('widgetPreviewSubscribe') : null,
        widgetPreviewUnsubscribe = document.getElementById('widgetPreviewUnsubscribe') ? document.getElementById('widgetPreviewUnsubscribe') : null,
        nl2gStylesConfig = document.getElementById('nl2gStylesConfig') ? document.getElementById('nl2gStylesConfig') : null;

    if (formUniqueCode) {
        var widgetStyleConfig = document.getElementById('widgetStyleConfig'),
            timer = 0,
            n2gSetUp = function () {
                if (widgetStyleConfig.textContent === null || widgetStyleConfig.textContent.trim() === "") {
                    widgetStyleConfig.textContent = JSON.stringify(n2gConfig, null, 2);
                } else {
                    n2gConfig = JSON.parse(widgetStyleConfig.textContent);
                }

                [].forEach.call(document.getElementsByClassName('n2go-colorField'), function (element) {
                    var field = element.name.split('.');
                    var style = getStyle(field[1], n2gConfig[field[0]]['style']);
                    if (style !== '') {
                        style = style.replace('#', '');
                        element.value = style;
                        element.focus();
                        element.blur();
                    }

                });
            };

        function getStyle(field, str) {
            var styleArray = str.split(';');

            for (var i = 0; i < styleArray.length; i++) {
                var styleField = styleArray[i].split(':');
                if (styleField[0].trim() == field) {
                    return styleField[1].trim();
                }
            }
            return '';
        }

        function updateConfig(element) {
            widgetStyleConfig.textContent = '';
            var formPropertyArray = element.name.split('.'),
                property = formPropertyArray[0],
                attribute = 'style',
                cssProperty = formPropertyArray[1],
                cssValue = '#' + element.value;

            var styleProperties;
            if (n2gConfig[property][attribute] == '') {
                styleProperties = cssProperty + ':' + cssValue;
            } else {
                styleProperties = updateString(n2gConfig[property][attribute], cssProperty, cssValue);
            }

            n2gConfig[property][attribute] = styleProperties;
            widgetStyleConfig.textContent = JSON.stringify(n2gConfig, null, 2);
        }

        function updateForm() {
            clearTimeout(timer);
            if (typeof (widgetPreviewSubscribe) != 'undefined' && widgetPreviewSubscribe !== null) {
                if (widgetPreviewSubscribe.children.length > 0) {
                    timer = setTimeout(function () {
                        var tmpSub = widgetPreviewSubscribe.querySelectorAll('form');
                        tmpSub[0].parentNode.removeChild(tmpSub[0]);
                        n2g('subscribe:createForm', n2gConfig, 'n2g_script_subscribe');
                    }, 100);
                }
            }

            if (typeof (widgetPreviewUnsubscribe) != 'undefined' && widgetPreviewUnsubscribe !== null) {
                if (widgetPreviewUnsubscribe.children.length > 0) {
                    timer = setTimeout(function () {
                        var tmpUnsub = widgetPreviewUnsubscribe.querySelectorAll('form');
                        tmpUnsub[0].parentNode.removeChild(tmpUnsub[0]);
                        n2g('unsubscribe:createForm', n2gConfig, 'n2g_script_unsubscribe');
                    }, 100);
                }
            }

        }

        function updateString(string, cssProperty, cssValue) {
            var stylePropertiesArray = string.split(';'),
                found = false,
                updatedString;
            // todo
            for (var i = 0; i < stylePropertiesArray.length - 1; i++) {
                var trimmedAttr = stylePropertiesArray[i].trim();
                var styleProperty = trimmedAttr.split(':');
                if (styleProperty[0] == cssProperty) {
                    styleProperty[1] = cssValue;
                    stylePropertiesArray[i] = styleProperty[0] + ':' + styleProperty[1];
                    found = true;
                    break;
                }
            }
            if (!found) {
                stylePropertiesArray[i] = cssProperty + ':' + cssValue;
            }

            updatedString = stylePropertiesArray.join(';');

            if (updatedString.slice(-1) !== ';') {
                updatedString += ';';
            }

            return updatedString;
        }

        function show() {
            var btnConfig = document.getElementById('btnShowConfig'),
                btnPreviewSubscribe = document.getElementById('btnShowPreviewSubscribe'),
                btnPreviewUnsubscribe = document.getElementById('btnShowPreviewUnsubscribe'),
                buttons = document.querySelectorAll('#n2gButtons li');

            buttons.forEach(function (button) {
                button.classList.remove('active');
            });

            if (typeof (widgetPreviewUnsubscribe) != 'undefined' && widgetPreviewUnsubscribe !== null) {
                widgetPreviewUnsubscribe.style.display = 'none';
            }

            if (typeof (widgetPreviewSubscribe) != 'undefined' && widgetPreviewSubscribe !== null) {
                widgetPreviewSubscribe.style.display = 'none';
            }

            nl2gStylesConfig.style.display = 'none';

            switch (this.id) {
                case 'btnShowPreviewUnsubscribe':
                    widgetPreviewUnsubscribe.style.display = 'block';
                    btnPreviewUnsubscribe.addClass('active');
                    break;
                case 'btnShowPreviewSubscribe':
                    widgetPreviewSubscribe.style.display = 'block';
                    btnPreviewSubscribe.addClass('active');
                    break;
                default:
                    nl2gStylesConfig.style.display = 'block';
                    btnConfig.addClass('active');
                    break;

            }
        }

        if (formUniqueCode) {
            var colorFields = document.getElementsByClassName('n2go-colorField');

            for (var i = 0; i < colorFields.length; i++) {
                colorFields[i].addEventListener('change', function (element) {
                    updateConfig(element.target);
                    updateForm();

                });
            }

            n2gSetUp();

            n2g('create', formUniqueCode);
            if (typeof (widgetPreviewSubscribe) !== 'undefined' && widgetPreviewSubscribe !== null) {
                if (widgetPreviewSubscribe.children.length > 0) {
                    n2g('subscribe:createForm', n2gConfig, 'n2g_script_subscribe');
                }
            }

            if (typeof (widgetPreviewUnsubscribe) != 'undefined' && widgetPreviewUnsubscribe !== null) {
                if (widgetPreviewUnsubscribe.children.length > 0) {
                    n2g('unsubscribe:createForm', n2gConfig, 'n2g_script_unsubscribe');
                }
            }

            // show();

            [].forEach.call(document.getElementById('n2gButtons').children, function (button) {
                button.addEventListener('click', show);
            });

            document.getElementById('resetStyles').addEventListener("click", function (e) {
                e.preventDefault();
                var defaultConfig = JSON.stringify(n2gConfigConst, null, 2),
                    http = new XMLHttpRequest(),
                    data = new FormData();

                data.append('style', defaultConfig);
                http.open('POST', 'nl2go/resetStyles.php', true);
                http.send(data);
                http.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        window.location = window.location.href;
                    }
                };
            });
        }
    }
});