<?php
function phone_exist($ddd, $number){
    global $wpdb;
    $meta_key = '_fullphone';
    $meta_value = substr($number, -8); // Get the last 8 digits of the phone number

    $query = $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
            WHERE meta_key = %s AND RIGHT(meta_value, 8) = %s",
            $meta_key, $meta_value
    );

    $valid = $wpdb->get_col( $query );
    return empty($valid);
}

function cadastro()
{
    if(isset($_POST['key']))
    {
      switch($_POST['key'])
      {
        case 'valid_user':
            $em = strtolower(trim($_POST['mail']));
            $ddd = trim($_POST['ddd']);
            $ddd = preg_replace('/\D/', '', $ddd);
            $tel = trim($_POST['phone']);
            $tel = preg_replace('/\D/', '', $tel);

            $valid_mail = true;
            $message_mail;
            $valid_phone= true;
            $message_phone;
            $debug = '';

            if( !is_email($em) )
            {
                $valid_mail = false;
                $message_mail = 'Ops... esse e-mail parece&nbsp;<strong>inválido</strong>';
            }
            if(email_exists( $em ))
            {
                $valid_mail = false;
                $message_mail = 'Esse e-mail&nbsp;<strong>já está cadastrado</strong>';
            }
            
            if( strlen($ddd) < 2 || strlen($tel) < 8 )
            {
                $valid_phone = false;
                $message_phone = 'Ops... o número de telefone parece&nbsp;<strong>inválido</strong>';
                $debug .= 'DDD: '.$ddd.' - TEL: '.$tel;
            }
            if( !phone_exist($ddd, $tel) )
            {
                $valid_phone = false;
                $message_phone = 'Esse telefone&nbsp;<strong>já está cadastrado</strong>';
            }
            

            if($valid_mail && $valid_phone)
            {
                $debug .= 'DDD: '.$ddd.' - TEL: '.$tel;
                wp_send_json_success(['debug' => $debug]);
            }
            else
            {
                wp_send_json_error(array(
                    'valid_email' => $valid_mail,
                    'message_email' => $message_mail,
                    'valid_phone' => $valid_phone,
                    'message_phone' => $message_phone,
                    'debug' => $debug
                ));
            }
        break;

        case 'register':
            $full_name = ucwords(sanitize_text_field( $_POST['name']));
            $names = explode(' ', $full_name);
            $first_name = reset($names);
            $last_name = count($names) > 1 ? end($names) : $first_name;
            
            $email = sanitize_email(strtolower(trim($_POST['mail'])));
            $ddd = preg_replace('/\D/', '', trim($_POST['ddd']));
            $phone = preg_replace('/\D/', '', trim($_POST['phone']));
            $pass = wp_hash_password($email);

            $user_data = array(
                'user_login'        => $email,
                'user_email'        => $email,
                'user_pass'         => $pass,
                'first_name'        => $first_name,
                'last_name'         => $last_name,
                'nickname'          => $full_name,
                'role'              => 'subscriber'
            );

            $user_id = wp_insert_user($user_data);
            if(is_wp_error($user_id))
            {
                wp_send_json_error(array(
                    'message' => 'Erro ao criar usuário',
                    'debug' => $user_id->get_error_message()
                ));
            }
            else
            {
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, '_ddd', $ddd);
                update_user_meta($user_id, '_phone', $phone);
                update_user_meta($user_id, '_fullphone', $ddd.$phone);
                wp_send_json_success([
                    'message' => 'Usuário criado com sucesso',
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'ddd' => $ddd,
                    'phone' => $phone
                ]);
            }
        break;

      }
    }
}
add_action('wp_ajax_cadastro', 'cadastro');
add_action('wp_ajax_nopriv_cadastro', 'cadastro');