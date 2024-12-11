<?php acf_form_head(); ?>
<?php
    global $wp_query; $pagenow;
    $user_id = esc_sql($_GET['member_id']);

    $user_fields = acf_get_fields('user_' . $user_id);
    //do_dump($user_fields);exit;

    $acf_fields = $_POST['acf'];

    // Actualizar los campos ACF del usuario
    // update_field('field_group_name', $acf_fields, 'user_' . $user_id);

?>
<div class="wrap">
    <h2><?php echo $user_id?'Editar usuario':'Nuevo usuario'; ?></h2>
    <form method="post" action="" style="margin-top: 30px;">
        <input type="hidden" name="custom_registration_nonce" value="<?php echo wp_create_nonce('custom-registration-nonce'); ?>">
        <table class="form-table" role="presentation" style="width: 50%;">
            <tbody>
                <tr class="form-field form-required">
                    <td>
                        <label for="username">Nombre de Usuario <span class="description">(obligatorio)</span></label>
                        <input name="username" type="text" id="username" value="" aria-required="true" autocapitalize="none" autocorrect="off" autocomplete="off" maxlength="60" required>
                        <input type="hidden" name="id_usuario" value="<?php echo $user_id; ?>">
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row"><label for="email">Correo Electrónico <span class="description">(obligatorio)</span></label></th>
                    <td><input name="email" type="email" id="email" value="" required></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><label for="first_name">Nombre</label></th>
                    <td><input name="first_name" type="text" id="first_name" value=""></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><label for="last_name">Apellidos</label></th>
                    <td><input name="last_name" type="text" id="last_name" value=""></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><label for="role">Rol</label></th>
                    <td>
                        <select name="role" id="role">
                            <option value="subscriber" selected>Suscriptor</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enviar aviso al usuario</th>
                    <td>
                        <input type="checkbox" name="send_user_notification" id="send_user_notification" value="1" checked="checked">
                        <label for="send_user_notification">Envía al nuevo usuario un correo electrónico con información sobre su cuenta</label>
                    </td>
                </tr>

                <?php
                    $group_id = 'group_650cb35a55e69';
                    $fields = acf_get_fields($group_id);
                ?>
                <?php if ($fields) : ?>
                    <?php foreach ($fields as $field) : ?>
                        <tr class="acf-field">
                            <th scope="row">
                                <label for="<?php echo esc_attr($field['key']); ?>"><?php echo esc_html($field['label']); ?></label>
                            </th>
                            <td>
                                <?php acf_render_field_wrap($field); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>


            </tbody>
        </table>
        <div class="postbox-container"><div class="meta-box-sortables ui-sortable"><div class="postbox acf-postbox">
            <?php foreach ($fields as $field) {
            ?>
            <div class="acf-field">
                <?php //do_dump($field);
                acf_render_field_wrap($field, 'div'); ?>
            </div>
            <?php
        }?></div></div></div>
        <p class="submit">
            <?php /* acf_form([
                'id' => 'createusersub',
                'submit_value' => 'Registrar nuevo suscriptor'
            ]); */ ?>
                <?php acf_enqueue_scripts(); ?>
            <!-- <input type="submit" name="submit" id="createusersub" class="button button-primary" value="Registrar nuevo suscriptor"> -->
        </p>
    </form>
</div>