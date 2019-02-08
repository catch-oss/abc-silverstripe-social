<?php

namespace Azt3k\SS\Social\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Silverstripe\SiteConfig\SiteConfig;
use Azt3k\SS\Social\DataObjects\OEmbedCacheItem;

class SocialAdmin extends Controller {

	private static $allowed_actions = array(
		'index',
		'htmlfragment'
	);

	public function ModuleDir() {
		return ABC_SOCIAL_DIR;
	}

	public function init() {
		parent::init();
		if (!Permission::check('CMS_ACCESS')) Security::permissionFailure();
	}

	public function index() {
		return [];
	}

	public function htmlfragment() {
		$url = $this->request->getVar('pUrl');
		$nocache = (int) $this->request->getVar('nocache');
		if ($embed = OEmbedCacheItem::fetch(array('url' => $url))) {
			if ($data = $embed->data()) return $data->html;
		}
	}
}
