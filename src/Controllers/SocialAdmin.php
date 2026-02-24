<?php

namespace Azt3k\SS\Social\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\Member;
use Azt3k\SS\Social\DataObjects\OEmbedCacheItem;

class SocialAdmin extends Controller {

	private static $allowed_actions = array(
		'index',
		'htmlfragment'
	);

	public function ModuleDir(): string
	{
		return ABC_SOCIAL_DIR;
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
