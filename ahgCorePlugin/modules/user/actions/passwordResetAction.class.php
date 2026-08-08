<?php
use AtomFramework\Http\Controllers\AhgController;
use AtomFramework\Services\Write\WriteServiceFactory;
use Illuminate\Database\Capsule\Manager as DB;

// PHPMailer is normally already available: the framework bootstrap loads its own
// composer autoloader, and PHPMailer is one of its dependencies. So ask before
// requiring anything.
//
// This used to require atom-framework/vendor/autoload.php outright and, failing
// that, three files from <root>/vendor/phpmailer/phpmailer/src. On an install
// from a plugin bundle the framework lives at plugins/ahgRuntimePlugin, so the
// first path does not exist and the fallback requires files that are not there
// either - a fatal on a page any signed-out user can reach, which is how password
// reset was failing.
if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class, true)) {
    $root = sfConfig::get('sf_root_dir');

    foreach ([
        $root.'/plugins/ahgRuntimePlugin/vendor/autoload.php',
        $root.'/atom-framework/vendor/autoload.php',
        $root.'/vendor/autoload.php',
    ] as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;

            break;
        }
    }
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class UserPasswordResetAction extends AhgController
{
    public static $NAMES = [
        'email',
    ];

    public function execute($request)
    {
        $this->form = new sfForm([], [], false);
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        foreach ($this::$NAMES as $name) {
            $this->addField($name);
        }

        // Handle CSRF token
        $csrfToken = $this->form->getCSRFToken();
        if ('' == $csrfToken) {
            $csrfToken = bin2hex(random_bytes(16));
        }

        $defaults = [
            '_csrf_token' => $csrfToken,
        ];

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters() + $defaults);

            if ($this->form->isValid()) {
                $email = $this->form->getValue('email');

                // Find user by email
                if (class_exists('Criteria')) {
                    $criteria = new Criteria();
                    $criteria->add(QubitUser::EMAIL, $email);
                    $user = QubitUser::getOne($criteria);
                } else {
                    $row = DB::table('user')->where('email', $email)->first();
                    $user = $row ? QubitUser::getById($row->id) : null;
                }

                if ($user) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Store token via WriteService
                    WriteServiceFactory::user()->savePasswordResetToken(
                        (int) $user->id,
                        $token,
                        $expiry
                    );

                    // Tell the user what actually happened.
                    //
                    // The send used to be fire-and-forget, with the success
                    // message printed unconditionally on the next line. With SMTP
                    // disabled - which is the shipped default, smtp_enabled 0 and
                    // no host - the token was stored, nothing was sent, and the
                    // user was told to go and check their inbox. Nothing appeared
                    // in the log either.
                    if ('1' !== (string) $this->getEmailSetting('smtp_enabled', '0')) {
                        $this->getUser()->setFlash('error', $this->context->i18n->__('Email is not configured on this site, so the reset message could not be sent. Please contact an administrator.'));
                    } elseif ($this->sendResetEmail($user, $token)) {
                        $this->getUser()->setFlash('notice', $this->context->i18n->__('Password reset instructions have been sent to your email.'));
                    } else {
                        $this->getUser()->setFlash('error', $this->context->i18n->__('The reset message could not be sent. Please contact an administrator.'));
                    }
                } else {
                    // Don't reveal if email exists (security best practice)
                    $this->getUser()->setFlash('notice', $this->context->i18n->__('If that email exists, reset instructions have been sent.'));
                }

                $this->redirect(['module' => 'user', 'action' => 'login']);
            }
        } else {
            // Bind defaults for GET request
            $this->form->bind($defaults);
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'email':
                $this->form->setDefault('email', '');
                $this->form->setValidator('email', new sfValidatorEmail(['required' => true]));
                $this->form->setWidget('email', new sfWidgetFormInput());
                break;
        }
    }

    /**
     * Get email setting from email_setting table
     */
    protected function getEmailSetting(string $key, $default = null)
    {
        // Bootstrap Laravel DB
        \AhgCore\Core\AhgDb::init();
        
        $setting = DB::table('email_setting')
            ->where('setting_key', $key)
            ->first();
        
        return $setting ? $setting->setting_value : $default;
    }

    /**
     * @return bool whether the message was actually handed to the mail server
     */
    protected function sendResetEmail($user, $token): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Get email settings from email_setting table
            $host = $this->getEmailSetting('smtp_host', 'localhost');
            $port = $this->getEmailSetting('smtp_port', 587);
            $smtp_secure = $this->getEmailSetting('smtp_encryption', 'tls');
            $username = $this->getEmailSetting('smtp_username', '');
            $password = $this->getEmailSetting('smtp_password', '');
            $from_email = $this->getEmailSetting('smtp_from_email', '');
            $from_name = $this->getEmailSetting('smtp_from_name', 'AtoM Archive');

            // Generate reset URL
            $resetUrl = $this->getController()->genUrl([
                'module' => 'user',
                'action' => 'passwordResetConfirm',
                'token' => $token
            ], true);

            // Build email body
            $body = "Hello,\n\n";
            $body .= "You requested a password reset for your account.\n\n";
            $body .= "Click the following link to reset your password:\n";
            $body .= $resetUrl . "\n\n";
            $body .= "This link will expire in 1 hour.\n\n";
            $body .= "If you did not request this reset, please ignore this email.\n\n";
            $body .= "Username: " . $user->username . "\n";

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = trim(strval($smtp_secure));
            $mail->Port = intval($port);

            $mail->setFrom(trim($from_email), $from_name);
            $mail->addAddress($user->email, $user->username);

            $mail->isHTML(false);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = $body;

            $mail->send();

            return true;
        } catch (\Throwable $e) {
            // Reported, not swallowed. This used to catch and log, and the caller
            // ignored the return value and told the user the mail was on its way
            // regardless - so a misconfigured or unreachable SMTP server was
            // indistinguishable from success, for the user and for us. Throwable
            // rather than Exception because a PHP 8 Error escaped the old catch
            // entirely.
            error_log('Password reset email could not be sent: '.($mail->ErrorInfo ?: $e->getMessage()));

            return false;
        }
    }
}
