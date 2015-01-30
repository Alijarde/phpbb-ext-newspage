<?php

/**
*
* @package NV Newspage Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace nickvergessen\newspage\controller;

use nickvergessen\newspage\newspage;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\event\dispatcher;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class main
 * Serving
 *	/news							[mandatory]
 *		/category/{forum_id}		[optional]
 *		/archive/{year}/{month}		[optional]
 *		/page/{page}				[optional]
 *	/news/{topic_id}				[optional]
 *
 * @package nickvergessen\newspage\controller
 */
class main
{
	/* @var config */
	protected $config;

	/* @var dispatcher */
	protected $dispatcher;

	/* @var template */
	protected $template;

	/* @var user */
	protected $user;

	/* @var helper */
	protected $helper;

	/* @var newspage */
	protected $newspage;

	/* @var string phpBB root path */
	protected $root_path;

	/* @var string phpEx */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param config $config
	 * @param dispatcher $dispatcher
	 * @param template $template
	 * @param user $user
	 * @param helper $helper
	 * @param newspage $newspage
	 * @param string $root_path
	 * @param string $php_ext
	 */
	public function __construct(config $config, dispatcher $dispatcher, template $template, user $user, helper $helper, newspage $newspage, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->dispatcher = $dispatcher;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->newspage = $newspage;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		if (!class_exists('bbcode'))
		{
			include($this->root_path . 'includes/bbcode.' . $this->php_ext);
		}
		if (!function_exists('get_user_rank'))
		{
			include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		}
	}

	/**
	 * Newspage controller to display multiple news
	 *
	 * Route must be a sequence of the following substrings,
	 * the order is mandatory:
	 *	/news							[mandatory]
	 *		/category/{forum_id}		[optional]
	 *		/archive/{year}/{month}		[optional]
	 *		/page/{page}				[optional]
	 *
	 * @param int	$forum_id		Forum ID of the category to display
	 * @param int	$year			Limit the news to a certain year
	 * @param int	$month			Limit the news to a certain month
	 * @param int	$page			Page to display
	 * @return Response A Symfony Response object
	 */
	public function newspage($forum_id, $year, $month, $page)
	{
		/**
		 * You can use this event to load settings on the newspage
		 *
		 * @event nickvergessen.newspage.newspage
		 * @var int	$forum_id		Forum ID of the category to display
		 * @var int	$year			Limit the news to a certain year
		 * @var int	$month			Limit the news to a certain month
		 * @var int	$page			Page to display
		 * @since 1.1.2
		 */
		extract($this->dispatcher->trigger_event('nickvergessen.newspage.newspage', array(
			'forum_id',
			'year',
			'month',
			'page',
		)));

		$this->newspage->set_category($forum_id)
			->set_archive($year, $month)
			->set_start(($page - 1) * $this->config['news_number']);

		return $this->base();
	}

	/**
	 * News controller to be accessed with the URL /news/{topic_id} to display a single news
	 *
	 * @param int	$topic_id		Topic ID of the news to display
	 * @return Response A Symfony Response object
	 */
	public function single_news($topic_id)
	{
		/**
		 * You can use this event to load settings on a single page view of the newspage
		 *
		 * @event nickvergessen.newspage.single_news
		 * @var int	$topic_id		Topic ID of the news to display
		 * @since 1.1.2
		 */
		extract($this->dispatcher->trigger_event('nickvergessen.newspage.single_news', array(
			'topic_id',
		)));

		$this->newspage->set_news($topic_id);

		return $this->base(false);
	}

	/**
	 * Base controller to be accessed with the URL /news/{id}
	 *
	 * @param	bool	$display_pagination		Force to hide the pagination
	 * @return Response A Symfony Response object
	 */
	public function base($display_pagination = true)
	{
		if ($this->config['news_archive_show'])
		{
			$this->newspage->generate_archive_list();
		}
		else
		{
			$this->newspage->count_num_pagination_items();
		}

		if ($display_pagination)
		{
			$this->newspage->generate_pagination();
		}
		if ($this->config['news_cat_show'])
		{
			$this->newspage->generate_category_list();
		}

		$this->newspage->base();
		$this->assign_images($this->config['news_user_info'], $this->config['news_post_buttons']);

		return $this->helper->render('newspage_body.html', $this->newspage->get_page_title());
	}

	/**
	 * @param bool $assign_user_buttons
	 * @param bool $assign_post_buttons
	 */
	protected function assign_images($assign_user_buttons, $assign_post_buttons)
	{
		$this->template->assign_vars(array(
			'REPORTED_IMG'			=> $this->user->img('icon_topic_reported', 'POST_REPORTED'),
		));

		if ($assign_user_buttons)
		{
			$this->template->assign_vars(array(
				'PROFILE_IMG'		=> $this->user->img('icon_user_profile', 'READ_PROFILE'),
				'SEARCH_IMG'		=> $this->user->img('icon_user_search', 'SEARCH_USER_POSTS'),
				'PM_IMG'			=> $this->user->img('icon_contact_pm', 'SEND_PRIVATE_MESSAGE'),
				'EMAIL_IMG'			=> $this->user->img('icon_contact_email', 'SEND_EMAIL'),
				'JABBER_IMG'		=> $this->user->img('icon_contact_jabber', 'JABBER'),
			));
		}

		if ($assign_post_buttons)
		{
			$this->template->assign_vars(array(
				'QUOTE_IMG'			=> $this->user->img('icon_post_quote', 'REPLY_WITH_QUOTE'),
				'EDIT_IMG'			=> $this->user->img('icon_post_edit', 'EDIT_POST'),
				'DELETE_IMG'		=> $this->user->img('icon_post_delete', 'DELETE_POST'),
				'INFO_IMG'			=> $this->user->img('icon_post_info', 'VIEW_INFO'),
				'REPORT_IMG'		=> $this->user->img('icon_post_report', 'REPORT_POST'),
				'WARN_IMG'			=> $this->user->img('icon_user_warn', 'WARN_USER'),
			));
		}
	}
}
