# A user model

## Model definition.
Create a file `app/models/Base/User.php` and add the following.

```php
<?php

namespace My\Models\Base;

use Phalcon\Mvc\Model;

use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;

/**
 * @Table("users")
 * @CreationTimeBehavior("created")
 * @ModificationTimeBehavior("modified")
 * @SoftDeleteBehavior("deleted")
 */
abstract class User extends Model
{
    /**
     * @IdentifierField
     * @Identity
     * @Primary
     * @GetSet
     */
    protected $ID;

    /**
     * @StringField(length=255, nullable=false)
     * @GetSet
     */
    protected $emailAddress;

    /**
     * @StringField(length=64, nullable=false)
     * @GetSet
     */
    protected $username;

    /**
     * @StringField(length=64, nullable=false)
     * @GetSet
     */
    protected $name;

    /**
     * @StringField(length=128, nullable=false)
     * @GetSet
     */
    protected $password;

    /**
     * @BooleanField(nullable=false)
     * @GetSet(get="isActive")
     */
    protected $active;

    /**
     * @EnumField(choices=["Superuser", "Administrator", "User"], nullable=false)
     * @GetSet
     */
    protected $role;

    /**
     * @DateTimeField(nullable=false)
     * @GetSet
     */
    protected $created;

    /**
     * @DateTimeField(nullable=true)
     * @GetSet
     */
    protected $modified;

    /**
     * @DateTimeField(nullable=true)
     * @GetSet
     */
    protected $deleted;

    /**
     * @DateTimeField(nullable=true)
     * @GetSet
     */
    protected $lastLogin;

    /**
     * @Validator
     */
    public function validateEmailAddress($validator)
    {
        $validator->add(
            "emailAddress",
            new EmailValidator([
                "message" => "The email address is not valid."
            ])
        );
    }

    /**
     * @Validator
     */
    public function validateUniqueness($validator)
    {
        $validator->add(
            "username",
            new UniquenessValidator([
                "message" => "The username is already in use."
            ])
        );

        $validator->add(
            "emailAddress",
            new UniquenessValidator([
                "message" => "The email address is already in use."
            ])
        );
    }
}
```

## Extending the model
Create a file `app/models/User.php` and add the following. This file extends the model above.

```php
<?php

namespace My\Models;

use Phalcon\DI;

use My\Models\Base;

class User extends Base\User
{
}
```
