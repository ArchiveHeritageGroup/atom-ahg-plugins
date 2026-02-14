<?php
use AtomFramework\Http\Controllers\AhgEditController;
use AtomFramework\Services\Write\WriteServiceFactory;

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

class UserRegisterAction extends AhgEditController
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'active',
        'confirmPassword',
        'email',
        'password',
        'username',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                // Dual-mode: Propel save (works via PropelBridge in both modes)
                if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
                    $this->resource->save(); // PropelBridge still available; Phase 4 will replace
                } else {
                    $this->resource->save();
                }

                if (null !== $this->context->getViewCacheManager()) {
                    // We just need to remove the cache for this user but sf_cache_key
                    // contents also the culture code, it worth the try? I don't think so
                    $this->context->getViewCacheManager()->remove('@sf_cache_partial?module=menu&action=_mainMenu&sf_cache_key=*');
                }

                // $this->redirect([$this->resource, 'module' => 'user']);
            }
        }
    }

    public function exists($validator, $values)
    {
        // Dual-mode: check for existing user by username or email
        if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
            $db = \Illuminate\Database\Capsule\Manager::class;
            $exists = $db::table('user')
                ->where('username', $values['username'])
                ->orWhere('email', $values['email'])
                ->exists();
            if ($exists) {
                throw new sfValidatorError($validator, $this->context->i18n->__('The username or e-mail address you entered is already in use'));
            }
        } else {
            $criteria = new Criteria();

            $criterion1 = $criteria->getNewCriterion(QubitUser::USERNAME, $values['username']);
            $criterion2 = $criteria->getNewCriterion(QubitUser::EMAIL, $values['email']);
            $criteria->add($criterion1->addOr($criterion2));

            if (0 < count(QubitUser::get($criteria))) {
                throw new sfValidatorError($validator, $this->context->i18n->__('The username or e-mail address you entered is already in use'));
            }
        }

        return $values;
    }

    protected function earlyExecute()
    {
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        $this->form->getValidatorSchema()->setPreValidator(new sfValidatorSchemaCompare(
            'password',
            '==',
            'confirmPassword',
            [],
            ['invalid' => $this->context->i18n->__('Your password confirmation did not match your password.')]
        ));

        $this->form->getValidatorSchema()->setPostValidator(
            new sfValidatorCallback(['callback' => [$this, 'exists']])
        );

        $this->resource = WriteServiceFactory::user()->newUser();
        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'username':
                $this->form->setDefault('username', $this->resource->username);
                $this->form->setValidator('username', new sfValidatorString(['required' => true]));
                $this->form->setWidget('username', new sfWidgetFormInput());

                break;

            case 'email':
                $this->form->setDefault('email', $this->resource->email);
                $this->form->setValidator('email', new sfValidatorEmail(['required' => true]));
                $this->form->setWidget('email', new sfWidgetFormInput());

                break;

            case 'password':
                $this->form->setDefault('password', null);

                // Use QubitValidatorPassword only when strong passwords are required
                if (sfConfig::get('app_require_strong_passwords')) {
                    $this->form->setValidator('password', new QubitValidatorPassword(
                        ['required' => !isset($this->getRoute()->resource)],
                        [
                            'invalid' => $this->context->i18n->__('Your password is not strong enough.'),
                            'min_length' => $this->context->i18n->__('Your password is not strong enough (too short).'),
                        ]
                    ));
                } else {
                    $this->form->setValidator('password', new sfValidatorString(['required' => !isset($this->getRoute()->resource)]));
                }

                $this->form->setWidget('password', new sfWidgetFormInputPassword());

                // no break
            case 'confirmPassword':
                $this->form->setDefault('confirmPassword', null);
                // Required field only if a new user is being created
                $this->form->setValidator('confirmPassword', new sfValidatorString(['required' => !isset($this->getRoute()->resource)]));
                $this->form->setWidget('confirmPassword', new sfWidgetFormInputPassword());

                break;
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

            case 'active':
                $this->resource->active = true;

                break;

            default:
                $this->resource[$name] = $this->form->getValue($name);
        }
    }
}
