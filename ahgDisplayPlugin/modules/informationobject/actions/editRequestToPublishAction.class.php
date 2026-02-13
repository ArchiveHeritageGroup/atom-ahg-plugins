<?php
use AtomExtensions\Services\SettingService;

/*
 * This file is part of Qubit Toolkit.
 *
 * Qubit Toolkit is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Qubit Toolkit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Qubit Toolkit.  If not, see <http://www.gnu.org/licenses/>.
 */

// Load PHPMailer via framework autoloader or vendor path
$frameworkAutoload = sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
if (file_exists($frameworkAutoload)) {
    require_once $frameworkAutoload;
} else {
    // Fallback to vendor directory
    $vendorPath = sfConfig::get('sf_root_dir') . '/vendor/phpmailer/phpmailer/src';
    require_once $vendorPath . '/PHPMailer.php';
    require_once $vendorPath . '/SMTP.php';
    require_once $vendorPath . '/Exception.php';
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use AtomFramework\Http\Controllers\AhgEditController;

/**
 * Request to Publish edit component.
 *
 * @author     Johan Pieterse
 */
class InformationObjectEditRequestToPublishAction extends AhgEditController
{
    public static $NAMES = [
        'unique_identifier',
        'rtp_name',
        'rtp_surname',
        'rtp_phone',
        'rtp_email',
        'rtp_institution',
        'rtp_motivation',
        'rtp_planned_use',
        'rtp_need_image_by',
        'object_id',
        'created_at',
        'cbReceipt',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();
                $this->resource->save(); // saves relations added in processForm()
                $this->informationObject = QubitInformationObject::getById($this->resource->id);

                $this->redirect([$this->resource, 'module' => 'informationobject']);
            }
        }
    }

    public function sendmail(
        $id,
        $rtp_name,
        $rtp_surname,
        $rtp_phone,
        $rtp_email,
        $rtp_institution,
        $rtp_planned_use,
        $rtp_motivation,
        $rtp_need_image_by
    ) {
        $mail = new PHPMailer(true);

        try {
            $host        = SettingService::getByNameAndScope('host', 'email');
            $port        = SettingService::getByNameAndScope('port', 'email');
            $smtp_secure = SettingService::getByNameAndScope('smtp_secure', 'email');
            $smtp_auth   = SettingService::getByNameAndScope('smtp_auth', 'email');
            $username    = SettingService::getByNameAndScope('email_username', 'email');
            $password    = SettingService::getByNameAndScope('password', 'email');
            $from        = SettingService::getByNameAndScope('from', 'email');
            $to          = SettingService::getByNameAndScope('to', 'email');
            $cc          = SettingService::getByNameAndScope('cc', 'email');
            $reply_to    = SettingService::getByNameAndScope('reply_to', 'email');
            $subject     = SettingService::getByNameAndScope('subject', 'email');
            $body        = SettingService::getByNameAndScope('body', 'email');

            // NOTE: these QubitSetting values are objects; if needed you can
            // change them to ->getValue(['sourceCulture' => true]) later.

            if ('' == $body) {
                $body = "Request to Publish details:\n".$id
                    ."\n Name & surname: ".$rtp_name.' '.$rtp_surname
                    ."\n Telephone: ".$rtp_phone
                    ."\n Email address: ".$rtp_email
                    ."\n Institution: ".$rtp_institution
                    ."\n Planned use: ".$rtp_planned_use
                    ."\n Motivation: ".$rtp_motivation
                    ."\n Need image by: ".$rtp_need_image_by;
            } else {
                $body = $body
                    ."\n".$id
                    ."\n Name & surname: ".$rtp_name.' '.$rtp_surname
                    ."\n Telephone: ".$rtp_phone
                    ."\n Email address: ".$rtp_email
                    ."\n Institution: ".$rtp_institution
                    ."\n Planned use: ".$rtp_planned_use
                    ."\n Motivation: ".$rtp_motivation
                    ."\n Need image by: ".$rtp_need_image_by;
            }

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true; // filter_var($smtp_auth, FILTER_VALIDATE_BOOLEAN);
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = trim(strval($smtp_secure)); // 'ssl' or 'tls'
            $mail->Port       = strval($port);              // e.g. 465 or 587

            $mail->From     = $rtp_email;
            $mail->FromName = $rtp_name.' '.$rtp_surname;

            // Get recipient name from settings, fallback to generic name
            $recipientName = SettingService::getByNameAndScope('recipient_name', 'email') ?: 'Archive';
            $mail->addAddress(trim($to), $recipientName);

            $mail->addReplyTo($reply_to, 'Reply');
            $mail->addCC($cc);

            $mail->Subject = $subject.' '.$id;
            $mail->Body    = $body;

            $mail->send();
        } catch (Exception $e) {
            echo "Email could not be sent. Error: {$mail->ErrorInfo}";
        }
    }

    protected function earlyExecute()
    {
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        $this->resource = $this->getRoute()->resource;

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }
    }

    protected function addField($name)
    {
        $user = null;
        if ($this->context->user->getAttribute('user_id')) {
            $user = QubitUser::getById($this->context->user->getAttribute('user_id'));
        }

        switch ($name) {
            case 'unique_identifier':
                // Store the logged-in user id, or leave blank
                $this->form->setDefault('unique_identifier', $user ? $user->id : '');
                $this->form->setValidator('unique_identifier', new sfValidatorString());
                $this->form->setWidget('unique_identifier', new sfWidgetFormInput());

                break;

            case 'rtp_name':
                $this->form->setDefault('rtp_name', $user ? $user->username : '');
                $this->form->setValidator('rtp_name', new sfValidatorString(['required' => true]));
                $this->form->setWidget('rtp_name', new sfWidgetFormInput());

                break;

            case 'rtp_surname':
                // If you later add surname to QubitUser, you can change this
                $this->form->setDefault('rtp_surname', $user ? $user->username : '');
                $this->form->setValidator('rtp_surname', new sfValidatorString(['required' => true]));
                $this->form->setWidget('rtp_surname', new sfWidgetFormInput());

                break;

            case 'rtp_phone':
                $this->form->setDefault('rtp_phone', '');
                $this->form->setValidator('rtp_phone', new sfValidatorString(['required' => true]));
                $this->form->setWidget('rtp_phone', new sfWidgetFormInput());

                break;

            case 'rtp_email':
                $this->form->setDefault('rtp_email', $user ? $user->email : '');
                $this->form->setValidator('rtp_email', new sfValidatorString(['required' => true]));
                $this->form->setWidget('rtp_email', new sfWidgetFormInput());

                break;

            case 'rtp_institution':
                $this->form->setDefault('rtp_institution', '');
                $this->form->setValidator('rtp_institution', new sfValidatorString(['required' => true]));
                $this->form->setWidget('rtp_institution', new sfWidgetFormInput());

                break;

            case 'rtp_planned_use':
                $this->form->setDefault('rtp_planned_use', '');
                $this->form->setValidator('rtp_planned_use', new sfValidatorString(['required' => true]));
                $this->form->setWidget('rtp_planned_use', new sfWidgetFormInput());

                break;

            case 'rtp_need_image_by':
                $this->form->setDefault('rtp_need_image_by', '');
                $this->form->setValidator('rtp_need_image_by', new sfValidatorString());
                $this->form->setWidget('rtp_need_image_by', $this->dateWidget());
                $this->form->getWidgetSchema()->rtp_need_image_by->setLabel(
                    $this->context->i18n->__('rtp_need_image_by')
                );

                break;

            case 'rtp_motivation':
                $this->form->setDefault('rtp_motivation', '');
                $this->form->setValidator('rtp_motivation', new sfValidatorString());
                $this->form->setWidget('rtp_motivation', new sfWidgetFormTextArea([], ['rows' => 4]));

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function dateWidget()
    {
        $widget = new sfWidgetFormInput();
        $widget->setAttribute('type', 'date');

        return $widget;
    }

    protected function processForm()
    {
        $rtp_name = $this->form->getValue('rtp_name') ?: '';
        $rtp_surname = $this->form->getValue('rtp_surname') ?: '';
        $rtp_phone = $this->form->getValue('rtp_phone') ?: '';
        $rtp_email = $this->form->getValue('rtp_email') ?: '';
        $rtp_institution = $this->form->getValue('rtp_institution') ?: '';
        $rtp_motivation = $this->form->getValue('rtp_motivation') ?: '';
        $rtp_planned_use = $this->form->getValue('rtp_planned_use') ?: '';
        $rtp_need_image_by = $this->form->getValue('rtp_need_image_by') ?: '';
        $unique_identifier = $this->form->getValue('unique_identifier') ?: '';

        $informationObj = QubitInformationObject::getById($this->resource->id);

        $rtpData = [
            'rtp_name' => $rtp_name,
            'rtp_surname' => $rtp_surname,
            'rtp_phone' => $rtp_phone,
            'rtp_email' => $rtp_email,
            'rtp_institution' => $rtp_institution,
            'rtp_motivation' => $rtp_motivation,
            'rtp_planned_use' => $rtp_planned_use,
            'rtp_need_image_by' => $rtp_need_image_by,
            'parent_id' => $unique_identifier,
            'object_id' => $informationObj->id,
            'status_id' => QubitTerm::IN_REVIEW_ID,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Also store logged-in user id in unique_identifier column
        if ($this->context->user->getAttribute('user_id')) {
            $rtpData['unique_identifier'] = $this->context->user->getAttribute('user_id');
        }

        $rtpId = \AtomFramework\Services\Write\WriteServiceFactory::requestToPublish()->createRequest($rtpData);

        $this->requesttopublish = $rtpId;

        // Create relation IO <-> RequestToPublish using the model helper
        $requesttopublish = QubitRequestToPublish::getById($rtpId);
        if ($requesttopublish) {
            $this->resource->addRequestToPublish($requesttopublish);
        }

        // Delete any marked relations if needed (leave this as-is)
        if (isset($this->request->delete_relations)) {
            foreach ($this->request->delete_relations as $item) {
                $params = $this->context->routing->parse(Qubit::pathInfo($item));
                $params['_sf_route']->resource->delete();
            }
        }

        // Send email
        $this->informationObject = $informationObj;
        $this->sendmail(
            $informationObj->id,
            $rtp_name,
            $rtp_surname,
            $rtp_phone,
            $rtp_email,
            $rtp_institution,
            $rtp_planned_use,
            $rtp_motivation,
            $rtp_need_image_by
        );
    }
}
