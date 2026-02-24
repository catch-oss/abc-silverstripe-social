<?php

namespace Azt3k\SS\Social\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use Azt3k\SS\Social\DataObjects\OEmbedCacheItem;

class SocialAdmin extends Controller {

	private static $allowed_actions = [
		'index',
		'htmlfragment',
	];

	public function ModuleDir(): string
	{
		$module = ModuleLoader::inst()->getManifest()->getModule('azt3k/abc-silverstripe-social');
		return $module ? $module->getRelativePath() : '';
	}

	public function init(): void
	{
		parent::init();
		if (!Permission::check('CMS_ACCESS')) Security::permissionFailure();
	}

	public function index(): mixed
	{
		return $this->renderWith('SocialAdmin');
	}

	public function htmlfragment(): mixed
	{
		$url = $this->request->getVar('pUrl');
		$nocache = (int) $this->request->getVar('nocache');
		if ($embed = OEmbedCacheItem::fetch(array('url' => $url))) {
			if ($data = $embed->data()) return $data->html;
		}
	}
}
