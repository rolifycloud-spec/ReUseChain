<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc_html( $email_subject ); ?></title>
</head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family: Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f4; padding:40px 0;">
    <tr>
        <td align="center">

            <table width="600" cellpadding="0" cellspacing="0" border="0"
                   style="background:#ffffff; border-radius:10px; overflow:hidden; width:600px; max-width:600px;">

                <tr>
                    <td style="padding:25px 25px 10px 25px; text-align:center; background:#ffffff;">
                        <img src="https://reusechain.ba/wp-content/uploads/logosnip.jpg"
                             alt="ReUseChain" width="160"
                             style="display:block; margin:0 auto; max-width:160px;">
                    </td>
                </tr>

                <tr>
                    <td style="padding:10px 30px 0 30px; text-align:left;">
                        <h2 style="margin:0; font-size:22px; color:#222;">
                            Zdravo <?php echo esc_html( $user->display_name ); ?>,
                        </h2>
                    </td>
                </tr>

                <tr>
                    <td style="padding:8px 30px 20px 30px; text-align:left;">
                        <p style="margin:0; font-size:15px; color:#444;">
                            Imate nove poruke na <strong>ReUseChain</strong> platformi:
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 30px 25px 30px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="background:#fafafa; border:1px solid #e1e1e1; border-radius:8px; padding:15px;">
                            <tr>
                                <td style="font-size:14px; color:#333; line-height:1.6;">
                                    <?php echo $messageHtml; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 30px 40px 30px; text-align:center;">
                        <a href="<?php echo esc_url( $thread_url ); ?>"
                           style="background:#0057b8; color:#ffffff; padding:14px 26px; 
                                  border-radius:6px; font-size:15px; font-weight:600;
                                  text-decoration:none; display:inline-block;">
                            Otvori konverzaciju
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px; text-align:center; background:#ffffff; border-top:1px solid #eee;">
                        <p style="margin:0; font-size:12px; color:#999;">
                            © <?php echo date('Y'); ?> ReUseChain — Sva prava zadržana.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
