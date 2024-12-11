var global_qty_users = 1;
jQuery(document).ready(function($) {
    const plan_amount_field = $('#payments_plan_amount input[type="number"]:eq(0)');
    const discount_field = $('#payments_discount input[type="number"]:eq(0)');
    const surcharge_field = $('#payments_surcharge input[type="number"]:eq(0)');
    const manual_amount_field = $('#payments_manual_amount input[type="number"]:eq(0)');
    const amount_field = $('#payments_amount input[type="number"]:eq(0)');

    function calculate_amount() {
        let price = plan_amount_field.val() !== '' ? parseFloat(plan_amount_field.val()) : 0.00;
        let manual_amount = manual_amount_field.val() != '' ? parseFloat(manual_amount_field.val()) : 0.00;
        if (manual_amount > 0) {
            amount_field.val(manual_amount.toFixed(2));
        } else {
            if (manual_amount === 0) {
                amount_field.val(price.toFixed(2));
            }

            let discount = discount_field.val() !== '' ? parseFloat(discount_field.val()) : 0.00;
            let surcharge = surcharge_field.val() !== '' ? parseFloat(surcharge_field.val()) : 0.00;
            let total = price;

            if (discount > 0) {
                total = total - discount;
                if (total < 0) total = 0.00;
                amount_field.val(total.toFixed(2));
            }

            if (surcharge > 0) {
                total = total + surcharge;
                amount_field.val(total.toFixed(2));
            }

        }
    }

    function get_susbcription_form_url() {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const renewValue = urlParams.get('renew');
        if (renewValue) return renewValue;
        else return 0;
    }

    function get_data_subscriber(suscriber_id) {
        return $.ajax({
            url: scriptVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_data_subscriber',
                suscriber_id: suscriber_id
            }
        });
    }

    function get_data_plan(plan_id, owner_id) {
        return $.ajax({
            url: scriptVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_data_plan',
                idplan: plan_id,
                owner_id: owner_id,
            }
        });
    }

    function set_renew_data(subscription_id) {
        return $.ajax({
            url: scriptVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'set_renew_data',
                subscription_id: subscription_id,
            }
        });
    }

    function setActionsToBeneficiaries() {
        const subscription_id = acf.get('post_id');
        if (subscription_id) {
            $.ajax({
                url: scriptVars.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_beneficiaries_from_subscription',
                    nonce: scriptVars.nonce,
                    subscription_id: subscription_id,
                },
                cache: false,
                success: function(response) {
                    if (response.status === 'success') {
                        const tds = $('tr.acf-row:not(".acf-clone") td[data-name="subscriptions_sub_check"]');
                        $.each(response.beneficiaries, function(i, item) {
                            let login = '';
                            if (item.login == 1) {
                                login = `<button title="Bloquear login" data-login="0" data-action="login-access" data-userid="${item.id}" data-ownername="${item.name}" data-subs="${subscription_id}" type="button" class="mini-button cancel">
                                            <i class="dashicons dashicons-lock"></i>
                                        </button>`;
                            } else if (item.login == 0) {
                                login = `<button title="Permitir login" data-login="1" data-action="login-access" data-userid="${item.id}" data-ownername="${item.name}" data-subs="${subscription_id}" type="button" class="mini-button success">
                                            <i class="dashicons dashicons-lock"></i>
                                        </button>`;
                            }

                            tds[i].innerHTML = `<button title="Enviar accesos por correo" data-action="send-login-access" data-userid="${item.id}" data-ownername="${item.name}" data-subs="${subscription_id}" type="button" class="mini-button info">
                                                    <i class="dashicons dashicons-email"></i>
                                                </button>
                                                ${login}
                                                <button title="Cambiar contraseña" data-action="set-password" data-userid="{item.id}" data-ownername="${item.name}" data-subs="${subscription_id}" type="button" class="mini-button pink">
                                                    <i class="dashicons dashicons-admin-network"></i>
                                                </button>
                                                <button data-style="zoom-in" data-size="xs" title="Preferencias de usuario" data-action="set-owner-preferences" data-userid="${item.id}" data-ownername="${item.name}" data-subs="${subscription_id}" type="button" class="mini-button info ladda-button">
                                                    <i class="dashicons dashicons-admin-settings"></i>
                                                </button>`;
                        });
                    } else {
                        swal.fire(
                            "Error!",
                            response.message,
                            "error"
                        )
                    }
                },
            });
        }
    }

    // $('#wpbody').prepend(`<div class="banner35years-admin-toolbar">
    //                                 <div class="banner35years-admin-toolbar-inner">
    //                                     <div class="banner35years-nav-wrap">
    //                                         <img style="width: 150px;" src="https://www.produ.com/wp-content/themes/Produ/assets/images/PRODU35LOGO.png">
    //                                     </div>
    //                                 </div>
    //                             </div>`);

    // $('.acf-field.acf-field-repeater.acf-field-650cb5822d6f5').css('margin-right: 20px');

    $('#wpbody .wrap .page-title-action:eq(0)').after(`<button data-style="expand-right" data-size="xs" id="_check_expirations" type="button" class="page-title-action bar-button ladda-button">Revisar vencimientos</button>`);

    acf.add_action('load', function($el) {
        const groupACF = $('#acf-group_664b9f1f3645c');
        const buttonRepeater = $('div[data-name="subscriptions_sub_beneficiaries"] .acf-button ');
        const selectField = $('.acf-field[data-name="subscriptions_sub_type"] select');
        const is_new_mode = window.location.href.includes("post-new.php?post_type=produ-subscription");

        const controlVisibility = function() {
            if (is_new_mode) {
                groupACF.addClass('group-disabled');
                buttonRepeater.hide();
            } else {
                const selectValue =  selectField.find('option:selected').text().toLowerCase();
                if (selectValue === 'corporativa') {
                    groupACF.removeClass('group-disabled');
                    buttonRepeater.show();
                } else {
                    groupACF.addClass('group-disabled');
                    buttonRepeater.hide();
                }
            }
        };

        if (is_new_mode) {
            const susbcription_id = get_susbcription_form_url();
            if (susbcription_id > 0) {
                $('.acf-field[data-name="subscriptions_sub_owner"] select').trigger('change');
                $('.acf-field[data-name="subscriptions_sub_plan"] select').trigger('change');
            }
        }

        if (!is_new_mode) {
            const suscriber_id = $('.acf-field[data-name="subscriptions_sub_owner"] select option:selected').val();
            if (suscriber_id) {
                get_data_subscriber(suscriber_id).done(function(result) {
                    let data = result.response;
                    const country = data.acf.country ? data.acf.country.countryName : '';
                    const department = data.acf.department_name ? data.acf.department_name : '';
                    const activity = data.acf.activity_name ? data.acf.activity_name : '';
                    $('#pro_name').html(data.name);
                    $('#pro_email').html(data.email);
                    $('#pro_country').html(country);
                    $('#pro_address').html(data.acf.address);
                    $('#pro_cp').html(data.acf.zipcode);
                    $('#pro_phone').html(data.acf.phone);
                    $('#pro_company').html(data.acf.subscriber_company);
                    $('#pro_position').html(data.acf.subscriber_position);
                    $('#pro_departament').html(department);
                    $('#pro_activity').html(activity);
                }).fail(function(err) {
                    console.error('Error:', err);
                });
            }

            const plan_id = $('.acf-field[data-name="subscriptions_sub_plan"] select option:selected').val();
            const owner_id = $('.acf-field[data-name="subscriptions_sub_owner"] select option:selected').val();
            if (plan_id) {
                get_data_plan(plan_id, owner_id).done(function(result) {
                    let data = result.response;
                    $('#plan_name').html(data.name);
                    $('#plan_duration').html(data.duration);
                    $('#plan_price').html(`$${data.price}`);
                    $('#plan_users').html(data.num_users);
                    $('#plan_description').html(data.description.replace(/\n/g, "<br>"));
                    global_qty_users = parseInt(data.num_users);
                }).fail(function(err) {
                    console.error('Error:', err);
                });
            }
            setActionsToBeneficiaries();
        }

        controlVisibility();

        selectField.on('change', function() {
            controlVisibility();
        });
    });

    acf.addAction('load_field/name=subscriptions_sub_begin_date', function( field ) {
        // field.$el.find('input:eq(1)').addClass('disable-input');
        // if ($(`#acf-${field.data.key}`).attr('readonly')) {
        //     field.$el.find('input:eq(1)').addClass('disable-input');
        // }
    });

    acf.addAction('load_field/name=subscriptions_sub_end_date', function( field ) {
        //Deshabilitar selector de fecha
        // field.$el.find('input:eq(1)').addClass('disable-input');
    });


    acf.addAction('load_field/name=subscriptions_sub_owner', function( field ) { });

    acf.addAction('select2_init', function( $select, args, settings, field ) {
        if (['subscriptions_sub_owner', 'payments_status', 'subscriptions_sub_type', 'subscriptions_sub_plan'].includes(field.data.name)) {
            if ($(`#acf-${field.data.key}`).attr('readonly')) {
                $(`#acf-${field.data.key}`).next('.select2.select2-container').addClass('select2-readonly');
            }
        }

        if (field.data.name === 'subscriptions_sub_type') {
            $(`#acf-${field.data.key}`).on('change', function () {
                $(`select[name="acf[field_66219d6a2b77e]"]`).val('').change();
            });
        }

        $('.status-select-field select').each(function() {
            const $self = $(this);
            const currentValue = $(this).val();
            switch(currentValue) {
                case 'activa':
                case 'inactiva':
                    $self.find('option[value="vencida"]').attr('disabled', 'disabled');
                    $self.find('option[value="archivada"]').attr('disabled', 'disabled');
                    break;
                case 'vencida':
                    $self.find('option[value="activa"]').attr('disabled', 'disabled');
                    $self.find('option[value="archivada"]').attr('disabled', 'disabled');
                    break;
                case 'archivada':
                    $self.next('.select2.select2-container').addClass('select2-readonly');
                    break;
            }
        });
    });

    acf.addFilter('select2_ajax_data', (data, args, $input, field, instance) => {
        if (['subscriptions_sub_plan'].includes(field.data.name)) {
            if (data.field_key === field.get('key')) {
                const args = {
                    name: 'subscriptions_sub_type'
                };
                const fields = acf.findFields( args );
                data.selection = fields[0].querySelector('select').value;
            }
        }
        return data;
    });

    $('.acf-field[data-name="subscriptions_sub_owner"] select').on('change', function () {
        const suscriber_id = $(this).val();
        if (!suscriber_id) return false;
        get_data_subscriber(suscriber_id).done(function(result) {
            let data = result.response;
            const country = data.acf.country ? data.acf.country.countryName : '';
            const department = data.acf.department_name ? data.acf.department_name : '';
            const activity = data.acf.activity_name ? data.acf.activity_name : '';
            $('#pro_name').html(data.name);
            $('#pro_email').html(data.email);
            $('#pro_country').html(country);
            $('#pro_address').html(data.acf.address);
            $('#pro_cp').html(data.acf.zipcode);
            $('#pro_phone').html(data.acf.phone);
            $('#pro_company').html(data.acf.subscriber_company);
            $('#pro_position').html(data.acf.subscriber_position);
            $('#pro_departament').html(department);
            $('#pro_activity').html(activity);

            const postID = acf.get('post_id');

            // Factura
            $('.acf-field[data-name="billing_name"] input:eq(0)').val(data.name);
            $('.acf-field[data-name="billing_email"] input:eq(0)').val(data.email);
            $('.acf-field[data-name="billing_phone"] input:eq(0)').val(data.acf.phone);
            $('.acf-field[data-name="billing_company"] input:eq(0)').val(data.acf.subscriber_company);
            $('.acf-field[data-name="billing_address"] input:eq(0)').val(data.acf.address);

            if (data.subscription != undefined) {
                if (data.subscription.ID != postID && data.subscription.status === 'activa') {
                    $('#poststuff').prepend(`<div class="notice notice-warning is-dismissible">
                                                <p>El suscriptor seleccionado ya cuenta con una suscripción activa, si activa esta suscripción, la actual será archivada.</p>
                                                <button type="button" class="notice-dismiss notice-dismiss-cs">
                                                    <span class="screen-reader-text">Descartar este aviso.</span>
                                                </button>
                                            </div>`);
                }
            }

            const begin_date = $('.acf-field[data-name="subscriptions_sub_begin_date"] input.hasDatepicker').val();
            const end_date = $('.acf-field[data-name="subscriptions_sub_end_date"] input.hasDatepicker').val();
            const textarea = document.querySelector('div[data-name="billing_description"] textarea');
            if (textarea) {
                var editor = tinymce.get(textarea.id);
                if (editor) {
                    editor.setContent(`
                        <b>Enviar a: </b> ${$('.acf-field[data-name="billing_email"] input').val()}<br>
                        <b>Ciclo de suscripción:</b> ${begin_date} - ${end_date}<br>
                        <b>Autorizado por:</b> PRODU
                    `);
                }
            }
        }).fail(function(err) {
            console.error('Error:', err);
        });
    });

    $('.acf-field[data-name="subscriptions_sub_plan"] select').on('change', function () {
        const idplan = $(this).val();
        const owner_id = $('.acf-field[data-name="subscriptions_sub_owner"] select option:selected').val();
        if (idplan) {
            get_data_plan(idplan, owner_id).done(function(result) {
                let data = result.response;
                plan_amount_field.val(parseFloat(data.price).toFixed(2));
                amount_field.val(parseFloat(data.price).toFixed(2));

                $('#plan_name').html(data.name);
                $('#plan_duration').html(data.duration);
                $('#plan_price').html(`$${data.price}`);
                $('#plan_users').html(data.num_users);
                $('#plan_description').html(data.description.replace(/\n/g, "<br>"));
                global_qty_users = parseInt(data.num_users);

                const begin_date = $('div[data-name="subscriptions_sub_begin_date"]').find('input.hasDatepicker');
                const end_date = $('div[data-name="subscriptions_sub_end_date"]').find('input.hasDatepicker');

                if (data.duration.toUpperCase() === 'ILIMITADO') {
                    data.end_date = '';
                }

                begin_date.val(data.begin_date);
                end_date.val(data.end_date);
                $('#acf-field_667d8bee0e3ae').val(data.end_date_hidden);

                const textarea = document.querySelector('div[data-name="billing_description"] textarea');
                if (textarea) {
                    var editor = tinymce.get(textarea.id);
                    if (editor) {
                        editor.setContent(`
                            <b>Enviar a: </b> ${$('.acf-field[data-name="billing_email"] input').val()}<br>
                            <b>Ciclo de suscripción:</b> ${begin_date.val()} - ${end_date.val()}<br>
                            <b>Autorizado por:</b> PRODU
                        `);
                    }
                }
            }).fail(function(err) {
                console.error('Error:', err);
            });
        }
    });

    discount_field.on('input', function() {
        surcharge_field.val('0.00');
        calculate_amount();
    });

    surcharge_field.on('input', function() {
        discount_field.val('0.00');
        calculate_amount();
    });

    manual_amount_field.on('input', function() {
        surcharge_field.val('0.00');
        discount_field.val('0.00');
        calculate_amount();
    });

    $(document).on('click', '.notice-dismiss-cs', function () {
        $(this).closest('.notice').remove();
    });

    $(document).on('click', 'button[data-action="cancel-subscription"]', function() {
        const $self = $(this);
        const owner = $(this).data('ownername');
        const postID = $(this).data('subs');
        if (postID) {
            Swal.fire({
                title: `Cancelar suscripción a<br> ${owner}`,
                html: `
                    Esta operación deshabilitará los newsletter en el propietario y beneficiarios (planes corporativos).<br>
                    Los usuarios tampoco podrán loguearse.
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Salir',
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: scriptVars.ajaxurl,
                        data: {
                            action: 'cancel_subscription',
                            nonce: scriptVars.nonce,
                            subscription_id: postID
                        },
                        cache: false,
                        success: function(response) {
                            response = JSON.parse(response);
                            if (response.status === 'success') {
                                swal.fire(
                                    "Ok!",
                                    response.message,
                                    "success"
                                );
                                $self
                                    .addClass('success')
                                    .removeClass('cancel')
                                    .html(`<i class="dashicons dashicons-yes"></i>`)
                                    .attr({
                                        'title': 'Activar suscripción'
                                    });

                                $self.data('action', 'activate-subscription');

                                $self.closest('tr').find('td.status.column-status').html('<span title="Inactiva" class="dashicons dashicons-no icons-status inactive"></span>');
                            } else {
                                swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                )
                            }
                        },
                    });
                }
            });
        } else {
            swal.fire(
                "Error!",
                "No se pudo obtener id de Suscripción.",
                "error"
            );
        }
    });

    $(document).on('click', 'button[data-action="activate-subscription"]', function() {
        const $self = $(this);
        const owner = $(this).data('ownername');
        const postID = $(this).data('subs');
        if (postID) {
            Swal.fire({
                title: `Activar suscripción a<br> ${owner}`,
                html: `
                    Esta operación habilitará los newsletter en el propietario y beneficiarios (planes corporativos).<br>
                    Los usuarios podrán loguearse.
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Salir',
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: scriptVars.ajaxurl,
                        data: {
                            action: 'activate_subscription',
                            nonce: scriptVars.nonce,
                            subscription_id: postID
                        },
                        cache: false,
                        success: function(response) {
                            response = JSON.parse(response);
                            if (response.status === 'success') {
                                let label = '';
                                switch(response.sub_status) {
                                    case 'activa':
                                        label = '<span title="Activa" class="dashicons dashicons-yes icons-status active"></span>';
                                        break;
                                    case 'activa-7':
                                        label = '<span title="Activa, por vencer" class="dashicons dashicons-update icons-status active-danger rotating-dashicon-2"></span>';
                                        break;
                                    case 'activa-15':
                                        label = '<span title="Activa, por vencer" class="dashicons dashicons-update icons-status update rotating-dashicon-5"></span>';
                                        break;
                                    case 'vencida':
                                        label = '<span title="Vencida" class="dashicons dashicons-clock icons-status expired"></span>';
                                        break;
                                }

                                swal.fire(
                                    "Ok!",
                                    response.message,
                                    "success"
                                );

                                $self
                                    .addClass('cancel')
                                    .removeClass('success')
                                    .html(`<i class="dashicons dashicons-no"></i>`)
                                    .attr({
                                        'title': 'Cancelar suscripción'
                                    });
                                $self.data('action', 'cancel-subscription');

                                    $self.closest('tr').find('td.status.column-status').html(label);
                            } else {
                                swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                )
                            }
                        },
                    });
                }
            });
        } else {
            swal.fire(
                "Error!",
                "No se pudo obtener id de Suscripción.",
                "error"
            );
        }
    });

    $(document).on('click', 'button[data-action="send-login-access"]', function() {
        const $self = $(this);
        const owner = $(this).data('ownername');
        const postID = $(this).data('subs');
        const userID = $(this).data('userid');
        if (postID) {
            Swal.fire({
                title: `Enviar accesos por correo a <br> ${owner}`,
                html: `
                    Esta operación enviará por correo los accesos para loguearse al propietario de la suscripción.
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Salir',
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: scriptVars.ajaxurl,
                        data: {
                            action: 'send_login_access',
                            nonce: scriptVars.nonce,
                            subscription_id: postID,
                            user_id: userID,
                        },
                        cache: false,
                        success: function(response) {
                            response = JSON.parse(response);
                            if (response.status === 'success') {
                                swal.fire(
                                    "Ok!",
                                    response.message,
                                    "success"
                                );
                            } else {
                                swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                )
                            }
                        },
                    });
                }
            });
        } else {
            swal.fire(
                "Error!",
                "No se pudo obtener id de Suscripción.",
                "error"
            );
        }
    });

    $(document).on('click', 'button[data-action="add-days-to-subscription"]', function() {
        const $self = $(this);
        const owner = $(this).data('ownername');
        const postID = $(this).data('subs');
        if (postID) {
            Swal.fire({
                title: `Agregar días de gracia a<br> ${owner}`,
                input: 'number',
                inputAttributes: {
                    autocapitalize: "off"
                },
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                cancelButtonText: 'Salir',
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                showLoaderOnConfirm: true,
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debe ingresar una cantidad';
                    }
                },
                inputAttributes: {
                    min: '1',
                    max: '365',
                    step: '1'
                },
                inputValue: 1,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: scriptVars.ajaxurl,
                        data: {
                            action: 'set_grace_period',
                            nonce: scriptVars.nonce,
                            period: result.value,
                            subscription_id: postID
                        },
                        cache: false,
                        success: function(response) {
                            response = JSON.parse(response);
                            if (response.status === 'success') {
                                swal.fire(
                                    "Ok!",
                                    response.message,
                                    "success"
                                );

                                if (!$self.find('span.number').length) {
                                    $self.html('<i class="dashicons dashicons-calendar"></i><span class="number"></span>');
                                }

                                $self.find('span.number').html(response.days);
                                $self.closest('tr').find('td.period.column-period').html(`${response.begin_date} - ${response.end_date}`);
                            } else {
                                swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                )
                            }
                        },
                    });
                }
            });
        } else {
            swal.fire(
                "Error!",
                "No se pudo obtener id de Suscripción.",
                "error"
            );
        }
    });

    $(document).on('click', 'button[data-action="set-owner-preferences"]', async function() {
        const l = Ladda.create($(this)[0]);
        const $self = $(this);
        const owner = $(this).data('ownername');
        const postID = $(this).data('subs');
        const userID = $(this).data('userid');
        if (postID) {
            l.start();
            $.ajax({
                type: 'POST',
                url: scriptVars.ajaxurl,
                data: {
                    action: 'get_newsletter_preferences',
                    nonce: scriptVars.nonce,
                    subscription_id: postID,
                    user_id: userID,
                },
                cache: false,
                success: async function(response) {
                    response = JSON.parse(response);
                    l.stop();
                    if (response.status === 'success') {
                        let template = '<table style="width:200px;margin:0 auto;"><tbody>';

                        if (response.selected) {
                            $.each(response.selected, function (i, item) {
                                const checked = item.local_status === 'subscribed' ? 'checked' : '';
                                template += `<tr>
                                                <td><input data-newsletter="${item.newsletter_wpid}" ${checked} name="preferences[]" type="checkbox" id="swal-input${i}"></td>
                                                <td style="text-align:left;">${item.title}</td>
                                            </tr>`;
                            });
                        }
                        template += '</tbody></table>';

                        const { value: preferences } = await Swal.fire({
                            title: `Preferencias Newsletters para<br> ${owner}`,
                            html: template,
                            focusConfirm: false,
                            showCancelButton: true,
                            confirmButtonText: 'Guardar',
                            cancelButtonText: 'Salir',
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#d33",
                            showLoaderOnConfirm: true,
                            preConfirm: () => {
                                const checkboxes = $('input[name="preferences[]"]');
                                let preferences = [];
                                $.each(checkboxes, function(i, item) {
                                    const newsletter_id = $(item).data('newsletter');
                                    if (newsletter_id) {
                                        preferences.push({
                                            newsletter_wpid: newsletter_id,
                                            local_status: item.checked ? 'subscribed' : 'unsubscribed',
                                        });
                                    }
                                });
                                return preferences;
                            }
                        });

                        if (preferences) {
                            $.ajax({
                                type: 'POST',
                                url: scriptVars.ajaxurl,
                                data: {
                                    action: 'set_newsletter_preferences',
                                    nonce: scriptVars.nonce,
                                    subscription_id: postID,
                                    preferences: preferences,
                                    user_id: userID,
                                },
                                cache: false,
                                success: function(response) {
                                    response = JSON.parse(response);
                                    if (response.status === 'success') {
                                        swal.fire(
                                            "Ok!",
                                            response.message,
                                            "success"
                                        );
                                    } else {
                                        swal.fire(
                                            "Error!",
                                            response.message,
                                            "error"
                                        );
                                    }
                                }
                            });

                        }
                    } else {
                        swal.fire(
                            "Error!",
                            response.message,
                            "error"
                        );
                    }
                },
            });
        }
    });

    $(document).on('click', 'button[data-action="login-access"]', function() {
        const $self = $(this);
        const owner = $(this).data('ownername');
        const postID = $(this).data('userid');
        const action = $(this).data('login');
        console.log("🚀 ~ $ ~ action:", action)
        let title = '';

        if (action == 0) title = 'Bloquear';
        if (action == 1) title = `Permitir`;

        if (postID) {
            Swal.fire({
                title: `${title} acceso a<br> ${owner}`,
                showCancelButton: true,
                confirmButtonText: title,
                cancelButtonText: 'Salir',
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: scriptVars.ajaxurl,
                        data: {
                            action: 'set_login_status',
                            nonce: scriptVars.nonce,
                            login_action: action,
                            user_id: postID
                        },
                        cache: false,
                        success: function(response) {
                            response = JSON.parse(response);
                            if (response.status === 'success') {
                                if (action == 0) {
                                    $self
                                        .addClass('success')
                                        .removeClass('cancel')
                                        .attr({
                                            'title': 'Permitir login'
                                        });
                                    $self.data('login', '1')
                                }

                                if (action == 1) {
                                    $self
                                        .addClass('cancel')
                                        .removeClass('success')
                                        .attr({
                                            'title': 'Bloquear login'
                                        });
                                    $self.data('login', '0')
                                }
                                swal.fire(
                                    "Ok!",
                                    response.message,
                                    "success"
                                );
                            } else {
                                swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                )
                            }
                        },
                    });
                }
            });
        } else {
            swal.fire(
                "Error!",
                "No se pudo obtener id de Suscripción.",
                "error"
            );
        }
    });

    $(document).on('click', 'button[data-action="set-password"]', function() {
        const $self = $(this);
        const owner = $(this).data('ownername');
        const userID = $(this).data('userid');
        if (userID) {
            Swal.fire({
                title: `Modificar la contraseña a<br> ${owner}`,
                input: 'text',
                showCancelButton: true,
                confirmButtonText: 'Modificar',
                cancelButtonText: 'Salir',
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                showLoaderOnConfirm: true,
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debe ingresar una contraseña';
                    }
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: scriptVars.ajaxurl,
                        data: {
                            action: 'set_user_password',
                            nonce: scriptVars.nonce,
                            password: result.value,
                            user_id: userID
                        },
                        cache: false,
                        success: function(response) {
                            response = JSON.parse(response);
                            if (response.status === 'success') {
                                swal.fire(
                                    "Ok!",
                                    response.message,
                                    "success"
                                );
                            } else {
                                swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                );
                            }
                        },
                    });
                }
            });
        } else {
            swal.fire(
                "Error!",
                "No se pudo obtener id de usuario.",
                "error"
            );
        }
    });

    $(document).on('click', '[data-event="add-row"]', function() {
        const $repeater = $(this).closest('.acf-repeater');
        const rowCount = $repeater.find('tr.acf-row:visible').length;

        if (rowCount >= global_qty_users) {
            swal.fire(
                'Advertencia!',
                'Ha sobrepasado el cupo límite de beneficiarios.',
                'warning'
            );
        }
    });

    $(document).on('click', '#_check_expirations', function() {
        const l = Ladda.create($(this)[0]);
        l.start();
        $.ajax({
            type: 'POST',
            url: scriptVars.ajaxurl,
            data: {
                action: 'check_expirations',
                nonce: scriptVars.nonce,
            },
            cache: false,
            success: async function(response) {
                response = JSON.parse(response);
                l.stop();
                if (response.status === 'success') {
                    let template = '<table style="width:330px;margin:0 auto;"><tbody>';

                    if (response.subs) {
                        $.each(response.subs, function (i, item) {
                            template += `<tr>
                                            <td><input data-subs="${item.ID}" name="subscriptions[]" type="checkbox" id="swal-input${i}"></td>
                                            <td style="text-align:left;">${item.title}</td>
                                        </tr>`;
                        });
                    }
                    template += '</tbody></table>';

                    const { value: subscriptions } = await Swal.fire({
                        title: `Suscripciones vencidas`,
                        html: template,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Actualizar',
                        cancelButtonText: 'Salir',
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            const checkboxes = $('input[name="subscriptions[]"]');
                            let subscriptions = [];
                            $.each(checkboxes, function(i, item) {
                                if (item.checked) {
                                    const subscription_id = $(item).data('subs');
                                    if (subscription_id) {
                                        subscriptions.push(subscription_id);
                                    }
                                }
                            });
                            return subscriptions;
                        }
                    });

                    if (subscriptions) {
                        $.ajax({
                            type: 'POST',
                            url: scriptVars.ajaxurl,
                            data: {
                                action: 'set_expirations',
                                nonce: scriptVars.nonce,
                                subscriptions: subscriptions,
                            },
                            cache: false,
                            success: function(response) {
                                response = JSON.parse(response);
                                if (response.status === 'success') {
                                    swal.fire(
                                        "Ok!",
                                        response.message,
                                        "success"
                                    );

                                    $.each(subscriptions, function(i, item) {
                                        $(`tr#post-${item} td.column-actions`).html('<b class="color-danger">Recargue la página para mostrar las acciones.</b>');
                                    });
                                } else {
                                    swal.fire(
                                        "Error!",
                                        response.message,
                                        "error"
                                    )
                                }

                            }
                        });
                    }
                } else if (response.status === 'warning') {
                    swal.fire(
                        "Advertencia!",
                        response.message,
                        "warning"
                    );
                } else {
                    swal.fire(
                        "Error!",
                        response.message,
                        "error"
                    );
                }
            },
        });
    });

    $('td[data-name="subscriptions_sub_check"] input.acf-switch-input').on('change', function() {

    });

    $('#generate-invoice').on('click', function(e) {
        e.preventDefault();
    
        var postId = generateInvoice.postId;
    
        $('#generate-invoice').prop('disabled', true);
        $('.loader').show();
    
        $.ajax({
            url: generateInvoice.ajaxurl,
            method: 'POST',
            data: {
                action: 'generate_invoice_pdf',
                post_id: postId,
            },
            success: function(response){
                $('.loader').hide();
                $('#generate-invoice').prop('disabled', false)
    
                if(response.success){
                    window.open(response.data.pdf_url, '_blank');
                } else {
                    alert('Hubo un error al generar el PDF');
                }
            },
            error: function() {
                alert('Hubo un error inesperado');
                $('.loader').hide();
                $('#generate-invoice').prop('disabled', false);
            }
        });
    });
});