<?php
namespace BotMan\Drivers\WeChat\Extensions;

use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Users\User as BotManUser;

class User extends BotManUser implements UserInterface
{

    /**
     * Shows whether the user has followed the official account.
     * 0: The user is not a follower, and you cannot obtain
     * other information about this user.
     *
     * @return integer|null
     */
    public function getSubscribe()
    {
        return $this->getInfo()['subscribe'] ?? null;
    }

    /**
     * The timestamp when the user follows the official
     * account or the last time if the user has followed several times
     *
     * @return integer|null
     */
    public function getSubscribeTime()
    {
        return $this->getInfo()['subscribe_time'] ?? null;
    }

    /**
     * Return the user language.
     *
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->getInfo()['language'] ?? null;
    }

    /**
     * Return the user city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->getInfo()['city'] ?? null;
    }

    /**
     * Return the user province.
     *
     * @return string|null
     */
    public function getProvince()
    {
        return $this->getInfo()['province'] ?? null;
    }

    /**
     * Return the user country.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->getInfo()['country'] ?? null;
    }

    /**
     * 1: Male; 2: Female; 0: Not Set
     *
     * @return integer|null
     */
    public function getSex()
    {
        return $this->getInfo()['sex'] ?? null;
    }

    /**
     * Profile photo URL. The last number in the URL shows the size of the square image,
     * which can be 0 (640*640), 46, 64, 96 and 132.
     * This parameter is null if the user hasn't set a profile photo
     *
     * @return string|null
     */
    public function getHeadImageUrl()
    {
        return $this->getInfo()['headimgurl'] ?? null;
    }

}