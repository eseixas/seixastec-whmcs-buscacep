(function () {
    'use strict';

    var cfg = window.SeixasTecBuscaCep;
    var $ = window.jQuery;
    if (!cfg || !cfg.endpoint || !$) {
        return;
    }

    var POSTCODE_SELECTOR = [
        'input[name="postcode"]',
        'input[name="PostCode"]',
        '#inputPostcode',
        '#postcode',
        'input[name$="[postcode]"]',
        'input[name*="postcode"]'
    ].join(',');

    var FIELD_SELECTORS = {
        country: ['select[name="country"]', 'input[name="country"]', '#inputCountry', '#country'],
        address1: ['input[name="address1"]', '#inputAddress1', '#address1'],
        address2: ['input[name="address2"]', '#inputAddress2', '#address2'],
        city: ['input[name="city"]', '#inputCity', '#city'],
        state: ['select[name="state"]', 'input[name="state"]', '#inputState', '#stateselect', '#state']
    };

    function boot($) {
        $(document).on('blur.buscacep', POSTCODE_SELECTOR, function () {
            lookup($(this));
        });

        $(document).on('input.buscacep', POSTCODE_SELECTOR, function () {
            var $el = $(this);
            if (cfg.mask) {
                applyMask($el);
            }
            var digits = digitsOf($el.val());
            if (digits.length !== 8) {
                return;
            }
            window.clearTimeout($el.data('buscacepTimer'));
            $el.data('buscacepTimer', window.setTimeout(function () {
                lookup($el);
            }, 400));
        });

        if (cfg.mask) {
            $(POSTCODE_SELECTOR).each(function () {
                applyMask($(this));
            });
        }
    }

    function digitsOf(value) {
        return String(value || '').replace(/\D+/g, '').slice(0, 8);
    }

    function applyMask($el) {
        var digits = digitsOf($el.val());
        var formatted = digits.length > 5 ? digits.slice(0, 5) + '-' + digits.slice(5) : digits;
        if ($el.val() !== formatted) {
            $el.val(formatted);
        }
    }

    function isBrazil($postcode) {
        var $country = closestField($postcode, FIELD_SELECTORS.country);
        if (!$country.length) {
            return true;
        }
        var value = String($country.val() || '').toUpperCase();
        return value === '' || value === 'BR' || value === 'BRAZIL' || value === 'BRASIL';
    }

    function closestField($postcode, selectors) {
        var $form = $postcode.closest('form');
        var selector = selectors.join(',');
        if ($form.length) {
            var $allPostcodes = $form.find(POSTCODE_SELECTOR);
            var $matches = $form.find(selector);
            if ($allPostcodes.length > 1 && $matches.length === $allPostcodes.length) {
                return $matches.eq($allPostcodes.index($postcode));
            }
            if ($matches.length === 1) {
                return $matches;
            }
        }

        var $node = $postcode;
        for (var depth = 0; depth < 10; depth += 1) {
            $node = $node.parent();
            if (!$node.length) {
                break;
            }
            var $found = $node.find(selector).not($postcode);
            if ($found.length) {
                return $found.first();
            }
            if ($node.is('form, body, html')) {
                break;
            }
        }

        return $();
    }

    function t(key) {
        return (cfg.i18n && cfg.i18n[key]) || '';
    }

    function warn($postcode, message, type) {
        $postcode.nextAll('.buscacep-alert').remove();
        if (!message) {
            return;
        }
        var level = type || 'warning';
        var $alert = $('<div class="alert buscacep-alert" role="status"></div>')
            .addClass('alert-' + level)
            .text(message);
        $postcode.after($alert);
        window.setTimeout(function () {
            $alert.fadeOut(400, function () {
                $alert.remove();
            });
        }, 8000);
    }

    function setStreet($field, street) {
        if (!$field.length || !street) {
            return;
        }
        var current = $.trim($field.val() || '');
        if (current && current.toLowerCase().indexOf(street.toLowerCase()) === 0) {
            return;
        }
        $field.val(street).trigger('change');
    }

    function setState($state, uf) {
        if (!$state.length || !uf) {
            return;
        }
        var wanted = String(uf).toUpperCase();
        if ($state.is('select')) {
            var match = '';
            $state.find('option').each(function () {
                var val = String(this.value || '');
                var text = String($(this).text() || '').toUpperCase();
                if (val.toUpperCase() === wanted || text === wanted || text.indexOf(wanted) !== -1) {
                    match = val;
                    return false;
                }
            });
            $state.val(match !== '' ? match : wanted).trigger('change');
            return;
        }
        $state.val(wanted).trigger('change');
    }

    function fill($postcode, data) {
        var $country = closestField($postcode, FIELD_SELECTORS.country);
        var setCountry = $country.length && !$country.val();
        if (setCountry) {
            $country.val(cfg.country || 'BR').trigger('change');
        }

        setStreet(closestField($postcode, FIELD_SELECTORS.address1), data.address1 || '');

        var $address2 = closestField($postcode, FIELD_SELECTORS.address2);
        if ($address2.length && data.address2) {
            $address2.val(data.address2).trigger('change');
        }

        var $city = closestField($postcode, FIELD_SELECTORS.city);
        if ($city.length && data.city) {
            $city.val(data.city).trigger('change');
        }

        var applyState = function () {
            setState(closestField($postcode, FIELD_SELECTORS.state), data.state || '');
        };

        if (setCountry) {
            window.setTimeout(applyState, 500);
        } else {
            applyState();
        }

        if (data.cep) {
            $postcode.val(data.cep);
        }
    }

    function lookup($postcode) {
        if (!$postcode.length || $postcode.prop('disabled') || $postcode.prop('readonly')) {
            return;
        }
        if (!isBrazil($postcode)) {
            return;
        }

        var digits = digitsOf($postcode.val());
        if (digits.length !== 8) {
            if (digits.length > 0) {
                warn($postcode, t('invalid'));
            }
            return;
        }

        if ($postcode.data('buscacepDone') === digits) {
            return;
        }

        $postcode.addClass('buscacep-loading');
        warn($postcode, t('lookingUp'), 'info');

        $.ajax({
            url: cfg.endpoint,
            method: 'POST',
            dataType: 'json',
            timeout: 15000,
            data: {
                cep: digits,
                token: cfg.token
            }
        }).done(function (data) {
            if (!data || !data.ok) {
                var code = (data && data.code) ? data.code : 'not_found';
                warn($postcode, t(code) || t('not_found'));
                return;
            }

            fill($postcode, data);
            $postcode.data('buscacepDone', digits);
            if (data.noStreet || data.code === 'no_street') {
                warn($postcode, t('no_street'));
                return;
            }
            warn($postcode, t('filled'), 'success');
        }).fail(function (xhr) {
            var code = 'network';
            if (xhr && xhr.responseJSON && xhr.responseJSON.code) {
                code = xhr.responseJSON.code;
            } else if (xhr && xhr.status === 429) {
                code = 'rate_limit';
            } else if (xhr && xhr.status === 403) {
                code = 'csrf';
            }
            warn($postcode, t(code) || t('network'));
        }).always(function () {
            $postcode.removeClass('buscacep-loading');
        });
    }

    $(boot);
})();
