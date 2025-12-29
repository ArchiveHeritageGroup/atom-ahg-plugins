<?php

class UserPasswordResetConfirmAction extends sfAction
{
    public static $NAMES = [
        'password',
        'confirmPassword',
    ];

    public function execute($request)
    {
        $token = $request->getParameter('token');
        
        // Validate token
        $criteria = new Criteria();
        $criteria->add(QubitUser::RESET_TOKEN, $token);
        $this->resource = QubitUser::getOne($criteria);

        if (!$this->resource || 
            !$this->resource->resetTokenExpiry || 
            strtotime($this->resource->resetTokenExpiry) < time()) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('Invalid or expired reset token.'));
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $this->form = new sfForm([], [], false);
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        $this->form->getValidatorSchema()->setPreValidator(new sfValidatorSchemaCompare(
            'password',
            '==',
            'confirmPassword',
            [],
            ['invalid' => $this->context->i18n->__('Your password confirmation did not match your password.')]
        ));

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
                $this->processForm();

                // Clear reset token
                $this->resource->resetToken = null;
                $this->resource->resetTokenExpiry = null;
                $this->resource->save();

                $this->getUser()->setFlash('notice', $this->context->i18n->__('Your password has been reset successfully. You can now log in with your new password.'));
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
            case 'password':
                $this->form->setDefault('password', null);

                if (sfConfig::get('app_require_strong_passwords')) {
                    $this->form->setValidator('password', new QubitValidatorPassword(
                        ['required' => true],
                        [
                            'invalid' => $this->context->i18n->__('Your password is not strong enough.'),
                            'min_length' => $this->context->i18n->__('Your password is not strong enough (too short).'),
                        ]
                    ));
                } else {
                    $this->form->setValidator('password', new sfValidatorString(['required' => true]));
                }

                $this->form->setWidget('password', new sfWidgetFormInputPassword());
                break;

            case 'confirmPassword':
                $this->form->setDefault('confirmPassword', null);
                $this->form->setValidator('confirmPassword', new sfValidatorString(['required' => true]));
                $this->form->setWidget('confirmPassword', new sfWidgetFormInputPassword());
                break;
        }
    }

    protected function processForm()
    {
        foreach ($this::$NAMES as $name) {
            $this->processField($this->form[$name]);
        }
    }

    protected function processField($field)
    {
        switch ($name = $field->getName()) {
            case 'password':
                if (0 < strlen(trim($this->form->getValue('password')))) {
                    $this->resource->setPassword($this->form->getValue('password'));
                }
                break;

            case 'confirmPassword':
                // Don't do anything for confirmPassword
                break;
        }
    }
}