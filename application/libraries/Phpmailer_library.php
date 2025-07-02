  <?php if (!defined('BASEPATH')) exit('No direct script access allowed');

    class Phpmailer_library
    {
        public function __construct()
        {
            log_message('Debug', 'PHPMailer class is loaded.');
        }

        public function sendMail($emailTujuan, $subjek, $isiMail)
        {
            require_once(APPPATH . 'third_party/PHPMailer/src/PHPMailer.php');
            require_once(APPPATH . 'third_party/PHPMailer/src/SMTP.php');

            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            // $mail->Host     = 'smtp.hostinger.com';
            // $mail->SMTPDebug = 1;

            // $mail->Host     = 'mail.karantinaindonesia.go.id';
            // $mail->SMTPAuth = true;
            // $mail->Username = 'notice@karantinaindonesia.go.id';
            // $mail->Password = 'R4h4s14';
            // $mail->SMTPSecure = 'STARTTLS';
            // $mail->Port     = 587;

            // $mail->Host     = 'smtp.kirimemail.com';
            // $mail->SMTPAuth = true;
            // $mail->Username = 'notice@karantinaindonesia.go.id';
            // $mail->Password = 'IqJKPsTM';
            // $mail->SMTPSecure = 'STARTTLS';
            // $mail->Port     = 587;
            // $mail->Username = 'info@karantinaindonesia.id';
            // $mail->Password = 'P4ssw0rd@Mail';
            // $mail->SMTPSecure = 'ssl';
            // $mail->Port     = 465;
            $mail->Host     = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;
            $mail->Username = '7a5b36002@smtp-brevo.com';
            $mail->Password = 'QN16yGfCx7EzbhJL';
            $mail->SMTPSecure = 'STARTTLS';
            $mail->Port     = 587;

            // $mail->setFrom('notice@karantinaindonesia.go.id', 'Prior Notice');
            $mail->setFrom('info@karantinaindonesia.id', 'Indonesian Quarantine Authority');
            // $mail->addReplyTo('info@karantinaindonesia.id', 'Indonesian Quarantine Authority');

            // Add a recipient
            $mail->addAddress($emailTujuan);

            // Add cc or bcc 
            // $mail->addCC('cc@example.com');
            // $mail->addBCC('bcc@example.com');

            // Email subject
            $mail->Subject = $subjek;

            // Set email format to HTML
            $mail->isHTML(true);

            // Email body content
            $mail->Body = $isiMail;

            // Send email
            if (!$mail->send()) {
                $respon = array(
                    'status' => FALSE,
                    'message' => 'Gagal kirim - ' . $mail->ErrorInfo
                );
            } else {
                $respon = array(
                    'status' => TRUE,
                    'message' => 'Email sukses terkirim!'
                );
            }
            return $respon;
        }
    }
