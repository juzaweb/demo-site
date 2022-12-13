<?php

namespace Juzaweb\DemoSite;

use Juzaweb\CMS\Abstracts\Action;
use Juzaweb\CMS\Facades\HookAction;

class DemoSiteAction extends Action
{
    public function handle()
    {
        $this->addAction(
            Action::BACKEND_CALL_ACTION,
            [$this, 'disableChange']
        );

        $this->addAction(
            Action::BACKEND_CALL_ACTION,
            [$this, 'setingForm']
        );
    
        $this->addAction(
            Action::BACKEND_INIT,
            [$this, 'disableMenus']
        );

        $this->addFilter(
            Action::BEFORE_PERMISSION_ADMIN,
            [$this, 'applyAdminPermission'],
            20,
            2
        );
    }

    public function setingForm()
    {
        HookAction::registerConfig(
            [
                'demo_user'
            ]
        );

        HookAction::addSettingForm(
            'demo-site',
            [
                'name' => __('Demo Site'),
                'view' => 'demo::setting',
            ]
        );
    }

    public function disableChange()
    {
        if (request()->method() != 'GET') {
            global $jw_user;
            $demoUser = get_config('demo_user');

            if ($jw_user->id == $demoUser) {
                $msg = __('You cannot edit the demo site.');

                if (request()->ajax()) {
                    response()->json(
                        [
                            'status' => false,
                            'data' => [
                                'message' => $msg
                            ],
                        ]
                    )->send();
                    die();
                }

                back()->withInput()->withErrors([$msg])->send();
                die();
            }
        }
    }

    public function applyAdminPermission($value, $user)
    {
        $demoUser = get_config('demo_user');
        if ($demoUser == $user->id) {
            return true;
        }

        return $value;
    }
    
    public function disableMenus()
    {
        $adminPrefix = config('juzaweb.admin_prefix');
        $disables = collect(config('demo_site.menu_disable', []))
            ->map(fn ($item) => $adminPrefix ."/". $item)
            ->toArray();
        
        if (request()->is($disables)) {
            abort(403);
        }
    }
}
