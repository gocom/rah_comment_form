<?php

/**
 * Rah_comment_form plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_comment_form
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	function rah_comment_form($atts, $thing=NULL) {
		
		global $is_article_list, $permlink_mode, $pretext;
		
		assert_article();
	
		extract(lAtts(array(
			'id' => __FUNCTION__,
			'class' => __FUNCTION__,
			'action' => $pretext['request_uri'].'#'.__FUNCTION__,
			'form' => __FUNCTION__,
		), $atts));
		
		if($thing !== null) {
			$thing = fetch_form($form);
		}
		
		$user = is_logged_in();
		
		if(!$user) {
			return;
		}
		
		rah_comment_form::get()->save_form();
		
		return 
			'<form id="'.htmlspecialchars($id).'" class="'.htmlspecialchars($class).'" method="post" action="'.htmlspecialchars($action).'">'.n.
				'<div>'.n.
					parse(EvalElse($thing, true)).n.
					hInput('rah_comment_form_save', '1').n.
					hInput('rah_comment_form_nonce', 'nonce').n. // TODO: implement nonce
				'</div>'.n.
			'</form>';
	}

/**
 * Returns comment message input
 */

	function rah_comment_message($atts) {
		
		$atts = array_merge(array(
			'rows' => 6,
			'cols' => 20,
		), $atts);
		
		$atts['name'] = 'rah_comment_message';
		
		foreach($atts as $name => $value) {
			$atts[$name] = htmlspecialchars($name).'="'.htmlspecialchars($value)."'";
		}
	
		return 
			'<textarea '.implode(' ', $atts).'></textarea>';
	}

class rah_comment_form {

	static public $instance = NULL;

	/**
	 * Gets an instance
	 */

	public function get() {
		if(self::$instance === NULL) {
			self::$instance = new rah_comment_form();
		}
		
		return self::$instance;
	}

	/**
	 * Saves a form
	 */
	
	public function save_form() {
		
		global $thisarticle;
		
		extract(psa(array(
			'rah_comment_form_save',
			'rah_comment_form_nonce',
			'rah_comment_form_message',
		)));
		
		$user = is_logged_in();
		
		if(!$user || !$rah_comment_form_save) {
			return;
		}
		
		if(!$rah_comment_form_message) {
			return;
		}

		$id = (int) article_id(array());
		$ip = doSlash(remote_addr());

		$message = doSlash(markup_comment(substr(trim($rah_comment_form_message), 0, 65535)));
		$name = doSlash($user['name']);
		$email = doSlash($user['email']);
		
		if(
			safe_row(
				'discussid',
				'txp_discuss',
				"name='{$name}' and parentid='{$id}' and message='{$message}' and ip='{$ip}'"
			)
		) {
			return;
		}
		
		$comment = 
			safe_insert(
				'txp_discuss',
				"parentid={$id},
				name='{$name}',
				email='{$email}',
				ip='{$ip}',
				message='{$message}',
				visible=1,
				posted=now()"
			);
		
		if($comment === false) {
			return;
		}
		
		safe_update(
			'textpattern',
			'comments_count=comments_count+1',
			'ID='.$id
		);
		
		//header('Location: '.permlink(array()).$sep.'#c'.$comment); TODO: redirect
	}
}
	
?>