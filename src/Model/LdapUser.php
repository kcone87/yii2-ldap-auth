<?php
declare(strict_types=1);

namespace kcone87\Yii2LdapAuth\Model;

use kcone87\Yii2LdapAuth\Exception\Yii2LdapAuthException;
use Yii;
use yii\base\BaseObject;
use yii\web\IdentityInterface;

/**
 * LDAP user model.
 *
 * @package kcone87\Yii2LdapAuth\Exception
 * @author Enock Willy <enokahoyah@gmail.com>
 * @date 07/06/2022
 */
class LdapUser extends BaseObject implements IdentityInterface
{
    /**
     * @var string LDAP UID of a user.
     */
    private $id;

    /**
     * @var string LDAP fullname of a user.
     */
    private $fullname;

    /**
     * @var string LDAP display name of a user.
     */
    private $displayname;

    /**
     * @var string Display name of a user.
     */
    private $username;

    /**
     * @var string Email of a user.
     */
    private $email;

    /**
     * @var string distinguished name of the user within LDAP.
     */
    private $dn;

    /**
     * LdapUser constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * @param string $fullname
     */
    public function setFullname(string $fullname): void
    {
        $this->fullname = $fullname;
    }

    /**
     * @return string
     */
    public function getDisplayname()
    {
        return $this->displayname;
    }

    /**
     * @param string $displayname
     */
    public function setDisplayname(string $displayname): void
    {
        $this->displayname = $displayname;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getDn(): string
    {
        return $this->dn;
    }

    /**
     * @param string $dn
     */
    public function setDn(string $dn): void
    {
        $this->dn = $dn;
    }

    /**
     * @param int|string $uid
     *
     * @return IdentityInterface|null
     */
    //using uid
    public static function findIdentity($uid)
    {
        $user = Yii::$app->ldapAuth->searchUid($uid);

        if (!$user) {
            return null;
        }

        return new static([
            //uid
            /*'Id' => $user['uid'][0],
            'Username' => $user['cn'][0],//fullname
            'Email' => $user['mail'][0],
            'Dn' => $user['dn'],*/
            
            //samaccountname
            'Id' => $user['samaccountname'][0],
            'Username' => $user['samaccountname'][0],//fullname
            'Fullname' => $user['name'][0],//fullname
            'Displayname' => $user['displayname'][0],//fullname
            'Email' => $user['mail'][0],
            'Dn' => $user['dn'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @throws Yii2LdapAuthException
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new Yii2LdapAuthException('Access token are not supported');
    }

    /**
     * {@inheritDoc}
     * @throws Yii2LdapAuthException
     */
    public function getAuthKey()
    {
        //throw new Yii2LdapAuthException('Auth keys are not supported');
    }

    /**
     * {@inheritDoc}
     * @throws Yii2LdapAuthException
     */
    public function validateAuthKey($authKey)
    {
        throw new Yii2LdapAuthException('Auth keys are not supported');
    }
}
