jQuery(document).ready(function($) {
    $(document).on('click', 'button[data-action="send"]', function() {
        const email = $(this).closest('div.form-group').find('input[name="invitations[]"]');
        const $self = $(this);

        if (!email.val()) {
            email.focus();
            return false;
        }

        const l = Ladda.create($(this)[0]);
        l.start();

        $.ajax({
            type: 'POST',
            url: subscriberVars.ajaxurl,
            dataType: 'json',
            data: {
                action: 'send_invitation',
                nonce: subscriberVars.nonce,
                email: email.val(),
            },
            cache: false,
            success: function(response) {
                if (response.status === 'success') {
                    swal.fire(
                        "Ok!",
                        response.message,
                        "success"
                    );
                    email.attr('readonly', true);
                    $self.closest('div.form-group').find('[data-place="actions"]').html(`
                        <button data-style="zoom-in" data-size="xs" class="btn btn-primary ladda-button" data-action="re-send" data-token="${response.token}" type="button">Re-enviar</button>
                        <button data-style="zoom-in" data-size="xs" class="btn btn-danger ladda-button" data-action="cancel" data-token="${response.token}" type="button">Cancelar</button>
                    `);
                } else {
                    email.val('');
                    swal.fire(
                        "Error!",
                        response.message,
                        "error"
                    );
                }
                l.stop();
            },
        });
    });

    $(document).on('click', 'button[data-action="re-send"]', function() {
        const token = $(this).data('token');
        const $self = $(this);

        if (token) {
            const l = Ladda.create($(this)[0]);
            l.start();

            $.ajax({
                type: 'POST',
                url: subscriberVars.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'resend_invitation',
                    nonce: subscriberVars.nonce,
                    token: token,
                },
                cache: false,
                success: function(response) {
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
                    l.stop();
                },
            });
        }
    });

    $(document).on('click', 'button[data-action="cancel"]', function() {
        const token = $(this).data('token');
        const $self = $(this);

        if (token) {
            const l = Ladda.create($(this)[0]);
            l.start();

            $.ajax({
                type: 'POST',
                url: subscriberVars.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'cancel_invitation',
                    nonce: subscriberVars.nonce,
                    token: token,
                },
                cache: false,
                success: function(response) {
                    if (response.status === 'success') {
                        swal.fire(
                            "Ok!",
                            response.message,
                            "success"
                        );

                        $self.closest('div.form-group').find('input[name="invitations[]"]').attr('readonly', false).val('');
                        $self.closest('div.form-group').find('[data-place="actions"]').html(`
                            <button data-style="zoom-in" data-size="xs" class="btn btn-success ladda-button" data-action="send" type="button">Enviar</button>
                        `);
                    } else {
                        swal.fire(
                            "Error!",
                            response.message,
                            "error"
                        );
                    }
                    l.stop();
                },
            });
        }
    });

    $(document).on('click', 'button[data-action="remove"]', function() {
        const memberID = $(this).data('member');
        if (memberID) {
            $.ajax({
                type: 'POST',
                url: subscriberVars.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'remove_member',
                    nonce: subscriberVars.nonce,
                    member_id: memberID,
                },
                cache: false,
                success: function(response) {
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

    $(document).on('click', '._my_prerence_check', function() {
        const $checkbox = $(this).siblings('input[type="checkbox"]');
        $checkbox.prop('checked', !$checkbox.prop('checked'));

        if ($checkbox.prop('checked')) {
            $(this).addClass('fa-circle-check text-success').removeClass('fa-circle-xmark text-danger');
        } else {
            $(this).addClass('fa-circle-xmark text-danger').removeClass('fa-circle-check text-success');
        }
    });

    $('#save-preferences').on('click', function() {
        const l = Ladda.create($(this)[0]);
        l.start();
        const checkboxes = $('input[name="preferences[]"]');
        let preferences = [];
        $.each(checkboxes, function(i, item) {
            const newsletter_id = $(item).val();
            if (newsletter_id) {
                preferences.push({
                    newsletter_wpid: newsletter_id,
                    local_status: item.checked ? 'subscribed' : 'unsubscribed',
                });
            }
        });

        $.ajax({
            type: 'POST',
            url: subscriberVars.ajaxurl,
            dataType: 'json',
            data: {
                action: 'set_newsletter_preferences',
                nonce: subscriberVars.nonce,
                preferences: preferences,
            },
            cache: false,
            success: function(response) {
                l.stop();
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
    });

    $('#save-personal-data').on('click', function() {
        const l = Ladda.create($(this)[0]);
        l.start();
        let data = {
            action: 'set_personal_data',
            nonce: subscriberVars.nonce,
        };

        const formFields = $('#personal-data-form').serializeArray();
        $.each(formFields, function(i, item) {
            data[item.name] = item.value;
        });

        $.ajax({
            type: 'POST',
            url: subscriberVars.ajaxurl,
            dataType: 'json',
            data: data,
            cache: false,
            success: function(response) {
                l.stop();
                if (response.status === 'success') {
                    swal.fire(
                        'OK!',
                        response.message,
                        'success'
                    );
                } else {
                    swal.fire(
                        'Error!',
                        response.message,
                        'error'
                    );
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                l.stop();
                swal.fire(
                    'Error!',
                    subscriberVars.error,
                    'error'
                );
            }
        });
    });

    $('#save-password').on('click', function() {
        const password = $('input[name="subscriptor_password"]').val();
        if (!password) {
            $('input[name="subscriptor_password"]').focus();
            return false;
        }

        const l = Ladda.create($(this)[0]);
        l.start();
        $.ajax({
            type: 'POST',
            url: subscriberVars.ajaxurl,
            dataType: 'json',
            data: {
                action: 'set_login_password',
                nonce: subscriberVars.nonce,
                password: password
            },
            cache: false,
            success: function(response) {
                l.stop();
                if (response.status === 'success') {
                    swal.fire(
                        'OK!',
                        response.message,
                        'success'
                    );
                } else {
                    swal.fire(
                        'Error!',
                        response.message,
                        'error'
                    );
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                l.stop();
                swal.fire(
                    'Error!',
                    subscriberVars.error,
                    'error'
                );
            }
        });
    });

    $('#show-password').on('click', function() {
        if ($('input[name="subscriptor_password"]').attr('type') === 'password') {
            $('input[name="subscriptor_password"]').attr('type', 'text');
            $(this).find('i').addClass('fa-eye').removeClass('fa-eye-slash');
        } else {
            $('input[name="subscriptor_password"]').attr('type', 'password');
            $(this).find('i').addClass('fa-eye-slash').removeClass('fa-eye');
        }
    });

    function change_plan() {
        const planID = 796; //Vip, fijo
        if (planID) {
            $.ajax({
                type: 'POST',
                url: subscriberVars.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'validate_plan',
                    nonce: subscriberVars.nonce,
                    plan_id: planID,
                },
                cache: false,
                success: function(response) {
                    console.log(response)
                    if (response.status === 'success') {
                        if (response.qtyNP < response.qtyCP) {
                            alert('Eliminar beneficiarios, plan con menos cupo, o si es plan individual, ya no podra compratir su suscripción.')
                        }
                    } else {

                    }
                },
            });
        }
    }
    //change_plan();
});