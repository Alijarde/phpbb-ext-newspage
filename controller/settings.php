<?php

/**
 *
 * @package NV Newspage Extension
 * @copyright (c) 2014 nickvergessen
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace nickvergessen\newspage\controller;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\request\request_interface;
use phpbb\user;
use Symfony\Component\HttpFoundation\Response;
use phpbb\exception\http_exception;

/**
 * Class settings
 *
 * @package nickvergessen\newspage\controller
 */
class settings
{
	/* @var auth */
	protected $auth;

	/* @var config */
	protected $config;

	/* @var request_interface */
	protected $request;

	/* @var user */
	protected $user;

	/* @var helper */
	protected $helper;

	/**
	 * Constructor
	 *
	 * @param auth $auth
	 * @param config $config
	 * @param request_interface $request
	 * @param user $user
	 * @param helper $helper
	 */
	public function __construct(auth $auth, config $config, request_interface $request, user $user, helper $helper)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->request = $request;
		$this->user = $user;
		$this->helper = $helper;
	}

	/**
	 * Newspage controller to display multiple news
	 * @return Response A Symfony Response object
	 * @throws http_exception
	 */
	public function manage()
	{
		$this->meta_refresh();

		// Redirect non admins back to the newspage
		if (!$this->auth->acl_get('a_board'))
		{
			throw new http_exception(403, 'NO_AUTH_OPERATION');
		}

		// Is someone trying to fool us?
		if (!check_form_key('newspage') || !$this->request->is_set_post('submit'))
		{
			throw new http_exception(400, 'FORM_INVALID');
		}

		$this->config->set('news_char_limit',	max(100, $this->request->variable('news_char_limit', 0)));
		$this->config->set('news_forums',		implode(',', $this->request->variable('news_forums', array(0))));
		$this->config->set('news_number',		max(1, $this->request->variable('news_number', 0)));
		$this->config->set('news_pages',		max(1, $this->request->variable('news_pages', 0)));
		$this->config->set('news_post_buttons',	$this->request->variable('news_post_buttons', false));
		$this->config->set('news_user_info',	$this->request->variable('news_user_info', false));
		$this->config->set('news_shadow',		$this->request->variable('news_shadow_show', false));
		$this->config->set('news_attach_show',	$this->request->variable('news_attach_show', false));
		$this->config->set('news_cat_show',		$this->request->variable('news_cat_show', false));
		$this->config->set('news_archive_show',	$this->request->variable('news_archive_show', false));

		return $this->helper->message($this->user->lang('NEWS_SAVED'));
	}

	/**
	 * Only put into a method for better mockability
	 *
	 * @return null
	 */
	public function meta_refresh()
	{
		meta_refresh(10, $this->helper->route('newspage_controller'));
	}
}
