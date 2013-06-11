<?php
/*
Plugin Name: Confluence
Plugin URI: http://github.com/edwardcasbon/wp-confluence/
Description: Use the Atlassian Confluence API to get Confluence content onto your website.
Author: Edward Casbon
Version: 1.0
Author URI: http://www.edwardcasbon.co.uk
*/
class Confluence {
	
	/**
	 * Plugin settings.
	 * 
	 */	
	protected $_settings;
	
	/**
	 * Kick off.
	 *
	 */
	public function __construct () {
		$this->_getSettings();
		add_action('wp_footer', array($this, 'confluenceJS'));
		add_action('admin_menu', array($this, 'addSettingsPage'));
		add_shortcode('confluence', array($this, 'confluenceShortcode'));
		add_action ( 'wp_ajax_getConfluenceSettings', array($this, 'getAjaxSettings'));
	}
		
	/** 
	 * Load the Confluence javascript.
	 * 
	 */
	public function confluenceJS () {
		wp_enqueue_script('confluence', plugins_url()."/wp-confluence/confluence.js", array(), false, true);
	}
		
	/**
	 * Enable the Confluence shortcode.
	 *
	 */
	public function confluenceShortcode ($atts) {
		$pageId = $atts['id'];
		if(!$pageId) return;
		$element 		= $this->_settings['element'];
		$elementClass 	= $this->_settings['elementClass'];
		return '<'. $element . ' class="' . $elementClass . '" data-pageid="' . $pageId . '"></' . $element . '>';
	}
		
	/**
	 * Add a settings page for the plugin.
	 *
	 */
	public function addSettingsPage () {
		add_submenu_page('options-general.php', 'Confluence Settings', 'Confluence', 'manage_options', 'confluence', array($this, 'createSettingsPage'));
	}
	
	/**
	 * Create the plugin settings page.
	 *
	 */	
	public function createSettingsPage () {
		$message = false;
		if(isset($_POST['submit'])) {
			if( empty($_POST['protocol']) || empty($_POST['websiteurl']) || empty($_POST['apiname']) || empty($_POST['apiversion']) || empty($_POST['element']) || empty($_POST['elementclass']) ) {
				$message = '<div id="message" class="error"><p>One of the settings you entered was missing. Please ensure all settings are entered.</p></div>';
			} else {
				$settings = array(
					'protocol' 		=> $_POST['protocol'],
					'websiteUrl'	=> $_POST['websiteurl'],
					'apiName'		=> $_POST['apiname'],
					'apiVersion'	=> $_POST['apiversion'],
					'element'		=> $_POST['element'],
					'elementClass'	=> $_POST['elementclass']
				);
				update_option('confluence_options', $settings);
				$message = '<div id="message" class="updated"><p>Settings saved</p></div>'; 
				$this->_getSettings();
			}
		}
		
		echo '<style>.indent{padding-left: 2em; margin-top:1em;}</style>' . 
			'<div class="wrap">' . 
			screen_icon() . 
			'<h2>Confluence Settings</h2>' . 
			'<p>Use the Confluence API plugin to pull content out of your Confluence website and into your Wordpress website.</p><h3>Usage</h3><p>Insert Confluence content into your website by including the following code wherever you want the content to be displayed: <pre>[confluence id="x"]</pre> where "x" is the Confluence page ID.' .
			$message .
			'<form action="" method="post" id="confluence">' .
				'<h3>API Details</h3>'.
				'<table class="form-table"><tbody>'.
				'<tr>'.
					'<th><label for="protocol">HTTP Protocol</label></th>'.
					'<td><input id="protocol" name="protocol" value="' . $this->_settings['protocol'] . '" type="text"/><p class="description">The protocol that your Confluence website is served through (e.g. "http", "https" etc)?</p></td>'.
				'</tr>'.
				'<tr>'.
					'<th><label for="websiteurl">Confluence website URL</label></th>'.
					'<td><input id="websiteurl" name="websiteurl" value="' . $this->_settings['websiteUrl'] . '" type="text" class="regular-text"/><p class="description">The URL of the Confluence website (without the protocol)?</p></td>'.
				'</tr>'.
				'<tr>'.
					'<th><label for="apiname">API name</label></th>'.
					'<td><input id="apiname" name="apiname" value="' . $this->_settings['apiName'] . '" type="text"/><p class="description">The name of the API (e.g. "prototype")?</p></td>'.
				'</tr>'.
				'<tr>'.
					'<th><label for="apiversion">API version</label></th>'.
					'<td><input id="apiversion" name="apiversion" value="' . $this->_settings['apiVersion'] . '" type="text"/><p class="description">The version of the API (e.g. "latest", "1")?</p></td>'.
				'</tr>'.
				'</tbody></table>'.
				'<h3>Plugin Details</h3>'.
				'<table class="form-table"><tbody>'.
				'<tr>'.
					'<th><label for="element">HTML element</label></th>'.
					'<td><input id="element" name="element" value="' . $this->_settings['element'] . '" type="text"/><p class="description">The HTML element that wraps the Confluence content (e.g. "div", "span", "article" etc)?</p></td>'.
				'</tr>'.
				'<tr>'.
					'<th><label for="elementclass">HTML element class</label></th>'.
					'<td><input id="elementclass" name="elementclass" value="' . $this->_settings['elementClass'] . '" type="text"/><p class="description">A class name for the element that the Confluence content is wrapped with</p></td>'.
				'</tr>'.
				'</tbody></table>'.
				'<input class="button-primary" type="submit" value="Save changes" name="submit"/>' .
			'</form>' .
			'</div>';
	}
	
	/**
	 * Utility function for getting plugin settings.
	 *
	 */
	protected function _getSettings () {
		if(!$this->_settings = get_option('confluence_options')) {
			$settings = array(
				'protocol'		=> 'http',
				'websiteUrl'	=> '',
				'apiName'		=> 'prototype',
				'apiVersion'	=> '1',
				'element'		=> 'div',
				'elementClass'	=> 'confluence'
			);
			add_option('confluence_options', $settings);
			$this->_settings = $settings;
		}
	}
	
	/**
	 * Get the settings via ajax.
	 * 
	 */
	public function getAjaxSettings () {
		die(json_encode($this->_settings));
	}
}

// Initialise the plugin.
new Confluence();