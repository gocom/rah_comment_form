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
		static $instance = 1;
		
		assert_article();
	
		extract(lAtts(array(
			'class' => __FUNCTION__,
			'action' => NULL,
			'form' => __FUNCTION__,
			'require_login' => 1,
		), $atts));
		
		$form_id = (string) md5($instance++);
		$id = __FUNCTION__ . '_' . $form_id;
		
		if($action === NULL) {
			$action = $pretext['request_uri'].'#'.$id;
		}
		
		if($thing === NULL) {
			$thing = fetch_form($form);
		}
		
		if($require_login && !is_logged_in()) {
			return;
		}
		
		$form = new rah_comment_form($form_id, $require_login);
		$form->save_form();
		
		return 
			'<form id="'.$id.'" class="'.htmlspecialchars($class).'" method="post" action="'.htmlspecialchars($action).'">'.n.
				'<div>'.n.
					parse(EvalElse($thing, true)).n.
					hInput('rah_comment_form_nonce', 'nonce').n. // TODO: implement nonce
					hInput('rah_comment_form_form_id', $form_id).n.
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
		
		$atts['name'] = 'rah_comment_form_message';
		
		foreach($atts as $name => $value) {
			$atts[$name] = htmlspecialchars($name).'="'.htmlspecialchars($value).'"';
		}
	
		return 
			'<textarea '.implode(' ', $atts).'>'.htmlspecialchars(rah_comment_form::get()->form()->message).'</textarea>';
	}

/**
 * Returns a comment web input
 */

	function rah_comment_input($atts) {
		
		$atts = array_merge(array(
			'type' => 'text',
			'name' => 'name',
		), $atts);
		
		$name = $atts['name'];
		$atts['value'] = rah_comment_form::get()->form()->$name;
		$atts['name'] = 'rah_comment_form_' . $name;
		
		foreach($atts as $name => $value) {
			$atts[$name] = htmlspecialchars($name).'="'.htmlspecialchars($value).'"';
		}
		
		return '<input '.implode(' ', $atts).' />';
	}

/**
 * Displays lis of form validation errors
 */

	function rah_comment_errors($atts) {

		extract(lAtts(array(
			'wraptag' => 'ul',
			'class' => __FUNCTION__,
			'break' => 'li',
		), $atts));

		$errors = rah_comment_form::get()->errors();
		return doWrap($errors, $wraptag, $break, $class);
	}

class rah_comment_form {

	/**
	 * @var obj Stores an instance of the class
	 */

	static public $instance = NULL;
	
	/**
	 * @var string Stores the form's instance id
	 */
	
	protected $form_id;
	
	/**
	 * @var array Form errors
	 */
	
	protected $errors = array();
	
	/**
	 * @var obj Form data
	 */
	
	protected $form;

	/**
	 * Gets an instance
	 */

	static public function get() {
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	
	public function __construct($form_id, $require_login) {
		$this->form_id = $form_id;
		$this->require_login = $require_login;
		
		$this->form = new stdClass();
		$user = is_logged_in();
		
		$this->form->name = null;
		$this->form->email = null;
		$this->form->web = null;
		
		if(is_array($user)) {
			foreach($user as $name => $value) {
				$this->form->$name = $value;
			}
		}
		
		$form = array('nonce', 'message', 'form_id', 'remember');
		$comment = array('name', 'web', 'email');
		
		if(!$this->require_login) {
			$form = array_merge($form, $comment);
		}
		
		foreach($form as $name) {
			$this->form->$name = ps(__CLASS__.'_'.$name);
		}
		
		$this->form->parent = (int) article_id(array());
		$this->form->ip = remote_addr();
		
		if($this->form->form_id !== $this->form_id) {
			foreach($form as $name => $value) {
				$this->form->$name = '';
			}
			
			if(!$this->require_login) {
				foreach($comment as $name) {
					$this->form->$name = cs(__CLASS__.'_'.$name);
				}
			}
		}
		
		if($this->require_login) {
			$this->form->remember = false;
		}
		
		self::$instance = $this;
	}
	
	/**
	 * Error
	 * @param string $msg
	 * @param string $field
	 * @return obj
	 */
	
	public function error($msg, $field=NULL) {
		$this->errors[] = $msg;
		return $this;
	}
	
	/**
	 * Gets a value
	 */
	
	public function __call($name, $args) {
		return $this->$name;
	}

	/**
	 * Saves a form
	 */

	public function save_form() {
		
		global $prefs;
		
		if($this->form->form_id !== $this->form_id) {
			return;
		}
		
		if(!$this->form->name) {
		
			if($prefs['comments_require_name']) {
				$this->error(gTxt('comment_name_required'));
				return; // name is required
			}
			
			if($this->require_login) {
				return; // log in
			}
		}
		
		if($prefs['comments_require_email'] && !$this->form->email) {
			$this->error(gTxt('comment_email_required'));
			return; // email is required
		}
		
		if(!$this->form->message) {
			$this->error(gTxt('comment_required'));
			return; // message is required
		}

		extract(doSlash((array) $this->form));
		$message = doSlash(markup_comment(substr(trim($this->form->message), 0, 65535)));
		
		if($this->form->remember) {
			foreach(array('name', 'email', 'web') as $n) {
				setcookie(__CLASS__.'_'.$n, $this->form->$n, time()+(365*24*3600), '/');
			}
		}
		
		if(
			safe_row(
				'discussid',
				'txp_discuss',
				"name='{$name}' and parentid='{$parent}' and message='{$message}' and ip='{$ip}'"
			)
		) {
			return;
		}
		
		$comment = 
			safe_insert(
				'txp_discuss',
				"parentid={$parent},
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
			'ID='.$parent
		);
		
		header('Location: '.permlink(array()).'#c'.$comment);
	}
}
	
?>