# Yii2 LDAP Auth

Simple extension to handle auth over LDAP in Yii 2 applications.

**This extension intended for applications that rely *only* on LDAP authentication and does not support access tokens.**

# Installation

```shell script
php composer.phar require --prefer-dist kcone87/yii2-ldap-auth "*"
```

or add

```
"kcone87/yii2-ldap-auth": "*"
```

to the require section of your composer.json file.

To always use the latest version from Github, in your composer.json, add this repository to the repositories section.

```
{
    ...
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/kcone87/yii2-ldap-auth"
        }
    ],
}
```

# Example of configuration and a use case

Considering [yii2-app-basic](https://github.com/yiisoft/yii2-app-basic):

### Configure the component in your configuration file and change user identity class

```php
'components' => [
    ...
    'ldapAuth' => [
        'class' => '\kcone87\Yii2LdapAuth\LdapAuth',
        'host' => 'your-ldap-hostname',
        'baseDn' => 'dc=work,dc=group',
        'searchUserName' => '<username for a search user>',
        'searchUserPassword' => '<password for a search user>',
        'userDomain' => '<domain to search user from>',//user domain
        // optional parameters and their default values
        'ldapVersion' => 3,             // LDAP version
        'protocol' => ['ldap://', 'ldaps://'],       // Protocol to use           
        'followReferrals' => false,     // If connector should follow referrals
        'port' => 636,                  // Port to connect to
        'loginAttribute' => 'uid',      // Identifying user attribute to look up for
        'ldapObjectClass' => 'person',  // Class of user objects to look up for
        'timeout' => 10,                // Operation timeout, seconds
        'connectTimeout' => 5,          // Connect timeout, seconds
    ],
    ...
    
    'user' => [
        'identityClass' => '\kcone87\Yii2LdapAuth\Model\LdapUser',
    ],
    ...
]
```

### Update methods in LoginForm class

```php
use kcone87\Yii2LdapAuth\Model\LdapUser;

...

public function validatePassword($attribute, $params)
{
    if (!$this->hasErrors()) {
        $user = LdapUser::findIdentity($this->username);

        if (!$user || !Yii::$app->ldapAuth->authenticate($user->getDn(), $this->password)) {
            $this->addError($attribute, 'Incorrect username or password.');
        }
    }
    
    //if user is a member of a group
    //Yii::$app->ldapAuth->authenticate($this->username, $user->getDn(), $this->password, $userGroup)//check user
}

...

public function login()
{
    if ($this->validate()) {
        return Yii::$app->user->login(
            LdapUser::findIdentity($this->username),
            $this->rememberMe
                ? 3600*24*30 : 0
        );
    }
    return false;
}
```

Now you can login with LDAP credentials to your application.