<?php

namespace BotMan\Drivers\WeChat\Providers;

use BotMan\Drivers\WeChat\WeChatDriver;
use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\WeChat\WeChatPhotoDriver;
use BotMan\Drivers\WeChat\WeChatVideoDriver;
use BotMan\Drivers\WeChat\WeChatLocationDriver;
use BotMan\Studio\Providers\StudioServiceProvider;

class WeChatServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__.'/../../stubs/wechat.php' => config_path('botman/wechat.php'),
            ]);

            $this->mergeConfigFrom(__DIR__.'/../../stubs/wechat.php', 'botman.wechat');
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(WeChatDriver::class);
        DriverManager::loadDriver(WeChatPhotoDriver::class);
        DriverManager::loadDriver(WeChatVideoDriver::class);
        DriverManager::loadDriver(WeChatLocationDriver::class);
    }

    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
