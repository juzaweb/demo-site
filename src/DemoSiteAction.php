<?php

namespace Juzaweb\DemoSite;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
            [$this, 'blockMenus']
        );

        $this->addFilter(
            Action::BEFORE_PERMISSION_ADMIN,
            [$this, 'applyAdminPermission'],
            20,
            2
        );
        
        $this->addFilter('get_admin_menu', [$this, 'disableMenus']);
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

    public function applyAdminPermission($value, $user): bool
    {
        $demoUser = get_config('demo_user');
        if ($demoUser == $user->id) {
            return true;
        }

        return $value;
    }
    
    public function blockMenus()
    {
        if ($this->isDisable()) {
            $adminPrefix = config('juzaweb.admin_prefix');
            $disables = collect(config('demo_site.menu_disable', []))
                ->map(fn ($item) => "{$adminPrefix}/{$item}")
                ->toArray();
    
            if (request()->is($disables)) {
                abort(403);
            }
        }
    }
    
    public function disableMenus($menus)
    {
        if ($this->isDisable()) {
            $disable = config('demo_site.menu_disable', []);
            return collect($menus)->filter(
                fn ($item) => !collect($disable)->contains(fn ($pattern) => Str::is($pattern, $item['url']))
            )->map(
                function ($item) use ($disable) {
                    if ($children = Arr::get($item, 'children')) {
                        $item['children'] = collect($children)->filter(
                            fn ($item) => !collect($disable)->contains(
                                fn ($pattern) => Str::is($pattern, $item['url'])
                            )
                        )->toArray();
                    }
                    return $item;
                }
            )->toArray();
        }
        
        return $menus;
    }
    
    private function isDisable(): bool
    {
        global $jw_user;
        $demoUser = get_config('demo_user');
    
        if ($jw_user->id == $demoUser) {
            return true;
        }
        
        return false;
    }
}
