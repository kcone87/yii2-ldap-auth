<?php
declare(strict_types=1);

namespace kcone87\Yii2LdapAuth;

use kcone87\Yii2LdapAuth\Exception\Yii2LdapAuthException;
use kcone87\Yii2LdapAuth\Model\LdapUser;
use yii\base\Component;

/**
 * Connector to LDAP server.
 *
 * @package kcone87\Yii2LdapAuth\Exception
 * @author Enock Willy <enokahoyah@gmail.com>
 * @date 07/06/2022
 */
class LdapAuth extends Component
{
    private const DEFAULT_TIMEOUT = 10;
    private const DEFAULT_CONNECT_TIMEOUT = 10;
    private const DEFAULT_PROTOCOL = 'ldap://';
    private const DEFAULT_PORT = 389;
    private const DEFAULT_LDAP_VERSION = 3;
    private const DEFAULT_LDAP_OBJECT_CLASS = 'person';
    private const DEFAULT_UID_ATTRIBUTE = 'uid';
    private const DEFAULT_USER_DOMAIN = '@example.com';
    private const DEFAULT_USER_GROUP = 'support';

    /**
     * @var string LDAP base distinguished name.
     */
    public $baseDn;

    /**
     * @var bool If connector should follow referrals.
     */
    public $followReferrals = false;

    /**
     * @var string Protocol to use.
     */
    public $protocol = self::DEFAULT_PROTOCOL;

    /**
     * @var string LDAP server URL.
     */
    public $host;

    /**
     * @var int LDAP port to use.
     */
    public $port = self::DEFAULT_PORT;

    /**
     * @var string username of the search user that would look up entries.
     */
    public $searchUserName;

    /**
     * @var string password of the search user.
     */
    public $searchUserPassword;

    /**
     * @var string LDAP object class.
     */
    public $ldapObjectClass = self::DEFAULT_LDAP_OBJECT_CLASS;

    /**
     * @var string attribute to look up for.
     */
    public $loginAttribute = self::DEFAULT_UID_ATTRIBUTE;

    /**
     * @var string attribute to look user from.
     */
    public $userDomain = self::DEFAULT_USER_DOMAIN;

    /**
     * @var string group to look user from.
     */
    public $userGroup = self::DEFAULT_USER_GROUP;

    /**
     * @var int LDAP protocol version
     */
    public $ldapVersion = self::DEFAULT_LDAP_VERSION;

    /**
     * @var int Operation timeout.
     */
    public $timeout = self::DEFAULT_TIMEOUT;

    /**
     * @var int Connection timeout.
     */
    public $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

    /**
     * @var resource|false
     */
    protected $connection;

    /**
     * Establish connection to LDAP server and bind search user.
     *
     * @throws Yii2LdapAuthException
     */
    protected function connect(): void
    {
        if (is_resource($this->connection)) {
            return;
        }

        $this->connection = ldap_connect($this->host, $this->port);

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldapVersion);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, $this->followReferrals);

        ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, $this->connectTimeout);
        ldap_set_option($this->connection, LDAP_OPT_TIMELIMIT, $this->timeout);

        if (!$this->connection) {
            throw new Yii2LdapAuthException(
                'Unable to connect to LDAP. Code '
                . ldap_errno($this->connection)
                . '. Message: '
                . ldap_error($this->connection)
            );
        }

        //bind using uid
        if ($this->loginAttribute == 'uid') {
            if (!@ldap_bind($this->connection, "uid=$this->searchUserName,$this->baseDn", $this->searchUserPassword)) {
                throw new Yii2LdapAuthException(
                    'Unable to bind LDAP search user. Code '
                    . ldap_errno($this->connection)
                    . '. Message: '
                    . ldap_error($this->connection)
                );
            }
        }

        //bind using samaccountname
        if ($this->loginAttribute == 'sAMAccountName' && $this->userDomain) {
            if (!@ldap_bind($this->connection, $this->searchUserName . $this->userDomain, $this->searchUserPassword)) {
                throw new Yii2LdapAuthException(
                    'Unable to bind LDAP search user. Code '
                    . ldap_errno($this->connection)
                    . '. Message: '
                    . ldap_error($this->connection)
                );
            }
        }
    }

    /**
     * @return resource
     * @throws Yii2LdapAuthException
     */
    public function getConnection()
    {
        $this->connect();
        return $this->connection;
    }

    /**
     * @param string|int $uid
     *
     * @return array Data from LDAP or null
     * @throws Yii2LdapAuthException
     */
    public function searchUid($uid): ?array
    {
        $result = ldap_search(
            $this->getConnection(),
            $this->baseDn,
            '(&(objectClass=' . $this->ldapObjectClass . ')(' . $this->loginAttribute . '=' . $uid . '))'
        );

        $entries = ldap_get_entries($this->getConnection(), $result);

        return $entries[0] ?? null;
    }


    /**
     * @param string $username
     * @param string $dn
     * @param string $password
     * @param string|null $group
     * @return bool
     * @throws Yii2LdapAuthException
     */
    public function authenticate(string $username, string $dn, string $password, ?string $group = null): bool
    {
        if (!@ldap_bind($this->getConnection(), $dn, $password)) {
            return false;
        }

        if (!$group) {
            return true;
        }

        return $this->isUserAMemberOf($dn, $username, $group);
    }

    /**
     * @throws Yii2LdapAuthException
     */
    protected function isUserAMemberOf(string $dn, string $user, string $group): bool
    {
        $result = ldap_search(
            $this->getConnection(),
            $this->baseDn,
            '(&(objectClass=user)(sAMAccountName='.$user.')(memberof='.$group.'))'
        );
        $entries = ldap_get_entries($this->getConnection(), $result);
        if ($entries){
            return true;
        }
        return false;
    }
}
