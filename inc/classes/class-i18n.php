<?php
/**
 * Internationalize bundle managment
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;
class I18n {
	use Singleton;
	private $translations = [];
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		
		add_action('init', [$this, 'load_plugin_translations'], 1, 0);
		add_action('plugins_loaded', [$this, 'load_plugin_textdomain'], 1, 0);

		add_action('wp_ajax_nopriv_futurewordpress/project/ajax/i18n/js', [$this, 'js_translates'], 10, 0);
		add_action('wp_ajax_futurewordpress/project/ajax/i18n/js', [$this, 'js_translates'], 10, 0);

		add_action('wp_ajax_nopriv_futurewordpress/project/ajax/i18n/number', [$this, 'number_translates'], 10, 0);
		add_action('wp_ajax_futurewordpress/project/ajax/i18n/number', [$this, 'number_translates'], 10, 0);
		
		add_filter('ctto/project/system/translate/string', [$this, 'translate_string'], 10, 4);
		add_filter('ctto/project/system/translate/number', [$this, 'translate_number'], 10, 4);
		add_filter('ctto/project/system/get_locale', [$this, 'get_locale'], 10, 1);
		
		add_action('wp_ajax_nopriv_futurewordpress/project/ajax/i18n/list', [$this, 'ajaxList'], 10, 0);
		add_action('wp_ajax_futurewordpress/project/ajax/i18n/list', [$this, 'ajaxList'], 10, 0);

		add_action('wp_ajax_nopriv_futurewordpress/project/ajax/i18n/update', [$this, 'updateList'], 10, 0);
		add_action('wp_ajax_futurewordpress/project/ajax/i18n/update', [$this, 'updateList'], 10, 0);

		// add_action('elementor/widget/render_content', [$this, 'elementor_widget_render_content'], 10, 2);
	}
	public function load_plugin_translations() {
		$this->translations = (array) $this->get_translations('teddy-bear-translations', []);
	}
	public function load_plugin_textdomain() {
		load_plugin_textdomain('ctto', false, dirname(plugin_basename(WOOFLUTTER_FILE__)) . '/languages');
	}
	public function js_translates() {
		$translates = [
			'submit' => __('Submit', 'ctto'),
			// ...
		];
		wp_send_json_success([
			'hooks'			=> ['ajaxi18nloaded'],
			'translates'	=> $translates
		]);
	}
	public function number_translates() {
		wp_send_json([
			'number' => number_format_i18n(
				$_REQUEST['number']??0,
				$_REQUEST['decimal']??2,
				false,
				false,
				$_REQUEST['locale']??get_user_locale()
			)
		], 200);
	}
	public function get_locale($default = false) {
		return apply_filters('ctto/project/system/getoption', 'translate-toonly', 'user') == 'user'?get_user_locale():get_locale();
	}
	/**
	 * https://wpml.org/wpml-hook/wpml_translate_single_string/
	 * @description Retrieves an individual (as opposed to a string that is part of a package**) text string translation. The filter looks for a string with matching $domain and $name. If it finds it, it looks for a translation in the current language or the language you specify. If a translation exists, it will return it. Otherwise, it will return the original string.
	 * 
	 * $text | (string) (Required) The string’s original value
	 * $domain | (string) (Required) The string’s registered domain
	 * $name | (string) (Required) The string’s registered name
	 * $language_code | (string) (Optional) Return the translation in this language. Default is NULL which returns the current language
	 */
	public function translate_string($text, $domain, $name, $language_code = NULL) {
		if ($language_code === NULL) {
			$language_code = apply_filters('ctto/project/system/getoption', 'translate-toonly', 'user') == 'user'?get_user_locale():get_locale();
		}
		if (!$text || empty(trim($text))) {
			return $text;
		}
		if (has_filter('wpml_translate_single_string')) {
			return apply_filters('wpml_translate_single_string', $text, $domain, $name, $language_code);
		}
		// count($this->translations) >= 1
		if ($text && !empty($text)
			// && $this->isEnglish($text)
			// && get_locale() == 'en_US'
		) {
			if ($language_code && !empty($language_code)) {
				$clonedText = str_replace(['\\', '\'', "'"], ['', "", ''], $text);
				foreach ($this->translations as $i => $row) {
					if (isset($row['en_US']) && $row['en_US'] == $clonedText && isset($this->translations[$i][$language_code])) {
						return $this->translations[$i][$language_code];
					}
				}
				if ($this->isEnglish($clonedText)) {
					$args = ['en_US' => $clonedText];
					$args[$language_code] = isset($args[$language_code])?$args[$language_code]:'';
					$this->translations[] = $args;
					$is_updated = $this->update_translations('teddy-bear-translations');
				}
				
			}
		}
		return __($text, $domain);
	}
	public function translate_number($number, $decimal = 0, $locale = NULL) {
		if (!$locale || empty($locale)) {
			$locale = apply_filters('ctto/project/system/get_locale', get_user_locale());
		}
		return number_format_i18n(
			$number,
			$decimal,
			false,
			false,
			$locale
		);
	}
	private function request_translations($text, $translate_to, $translate_from, $language_code) {
		if (true) {
			// $result = $this->lectoTranslateText([$text], [$translate_to], $translate_from, $language_code);
			$result = $this->libreTranslateText($text, $translate_to, $translate_from, $language_code);
			echo $result;
			if (! $result) {
				return false;
			} else {
				$translationExists = false;
				foreach ($this->translations as $i => $row) {
					if (isset($row[$translate_from]) && $row[$translate_from] == $text) {
						$this->translations[$i][$language_code] = $result;
						$translationExists = true;
					}
				}
				if (!$translationExists) {
					$this->translations[] = [
						$translate_from	=> $text,
						$language_code	=> $result
					];
				}
				if ($this->update_translations('teddy-bear-translations')) {
					return true;
				}
			}
		}
		return false;
	}
	public function lectoTranslateText($texts, $to, $from) {
		$apiKey = apply_filters('ctto/project/system/getoption', 'translate-api', 'V13Y91F-DR14RP6-KP4EAF9-S44K7SX');
		$url = 'https://api.lecto.ai/v1/translate/text';
		
		$postData = json_encode(array(
			'texts' => $texts,
			'to' => $to,
			'from' => $from
		));
	
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-API-Key: ' . $apiKey,
			'Content-Type: application/json',
			'Accept: application/json'
		));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		$response = curl_exec($ch);
		curl_close($ch);
	
		if (curl_error($ch)) {
			return false;
		}

		return $response;
	}
	/**
	 * https://libretranslate.com/
	 */
	public function libreTranslateText($text, $translate_to, $translate_from, $language_code) {
		$apiKey = apply_filters('ctto/project/system/getoption', 'translate-api', '');
		$url = 'https://libretranslate.com/translate';
		
		// fetch("", {
		// 	"headers": {
		// 	  "accept": "*/*",
		// 	  "accept-language": "en-GB,en-US;q=0.9,en;q=0.8,bn;q=0.7",
		// 	  "cache-control": "no-cache",
		// 	  "content-type": "multipart/form-data; boundary=----WebKitFormBoundaryAvo1Nunz89GdBmZ4",
		// 	  "pragma": "no-cache",
		// 	  "sec-ch-ua": "\"Not_A Brand\";v=\"8\", \"Chromium\";v=\"120\", \"Google Chrome\";v=\"120\"",
		// 	  "sec-ch-ua-mobile": "?0",
		// 	  "sec-ch-ua-platform": "\"Windows\"",
		// 	  "sec-fetch-dest": "empty",
		// 	  "sec-fetch-mode": "cors",
		// 	  "sec-fetch-site": "same-origin"
		// 	},
		// 	"referrer": "https://libretranslate.com/?source=auto&target=he&q=Hi",
		// 	"referrerPolicy": "strict-origin-when-cross-origin",
		// 	"body": "------WebKitFormBoundaryAvo1Nunz89GdBmZ4\r\nContent-Disposition: form-data; name=\"q\"\r\n\r\nHi\r\n------WebKitFormBoundaryAvo1Nunz89GdBmZ4\r\nContent-Disposition: form-data; name=\"source\"\r\n\r\nauto\r\n------WebKitFormBoundaryAvo1Nunz89GdBmZ4\r\nContent-Disposition: form-data; name=\"target\"\r\n\r\nhe\r\n------WebKitFormBoundaryAvo1Nunz89GdBmZ4\r\nContent-Disposition: form-data; name=\"format\"\r\n\r\ntext\r\n------WebKitFormBoundaryAvo1Nunz89GdBmZ4\r\nContent-Disposition: form-data; name=\"api_key\"\r\n\r\n\r\n------WebKitFormBoundaryAvo1Nunz89GdBmZ4\r\nContent-Disposition: form-data; name=\"secret\"\r\n\r\nKZ5H9Y8\r\n------WebKitFormBoundaryAvo1Nunz89GdBmZ4--\r\n",
		// 	"method": "POST",
		// 	"mode": "cors",
		// 	"credentials": "include"
		//   });
		
		$postData = json_encode(array(
			'q'			=> $text,
			'source'	=> $translate_from, // 'auto',
			'target'	=> $translate_to,
			'format'	=> 'text',
			'secret'	=> 'KZ5H9Y8',
			'api_key'	=> $apiKey
		));
	
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			// 'X-API-Key: ' . $apiKey,
			'Content-Type: application/json',
			'Accept: application/json'
		));
		curl_setopt($ch, CURLOPT_REFERER, 'https://libretranslate.com/translate');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		$response = curl_exec($ch);
		curl_close($ch);
	
		if (curl_error($ch)) {
			return false;
		}

		return $response;
	}
	public function get_translations($key, $def = []) {
		$filename = WOOFLUTTER_DIR_PATH . '/templates/' . $key.'.txt';
		if (file_exists($filename) && !is_dir($filename)) {
			$contents = file_get_contents($filename);
			if (!empty(trim($contents))) {
				// return (array) maybe_unserialize($contents);
				return (array) json_decode($contents, true);
			}
		} else {
			$this->translations = [];
			$is_updated = $this->update_translations('teddy-bear-translations');
		}
		return $def;
	}
	public function update_translations($key) {
		$filename = WOOFLUTTER_DIR_PATH . '/templates/' . $key.'.txt';
		// $this->translations && is_array($this->translations)
		if (true) {
			$handle = fopen($filename, "w+");
			// $content = maybe_serialize($this->translations);
			$content = json_encode($this->translations);
			if (fwrite($handle, $content) === FALSE) {
				fclose($handle);
				return false;
			} else {
			}
			fclose($handle);
			return true;
		}
		return false;
	}
	public function ajaxList() {
		wp_send_json_success([
			'hooks' => ['ajaxList-success'],
			'list'  => $this->translations
		], 200);
	}
	public function updateList() {
		$json = ['hooks' => ['updateI18nList-failed']];
		if (isset($_POST['i18n']) && is_array($_POST['i18n'])) {

			$this->translations = $_POST['i18n'];
			$is_updated = $this->update_translations('teddy-bear-translations');

			$json['list'] = $_POST['i18n'];
			$json['hooks'] = ['updateI18nList-success'];
		}
		wp_send_json_success($json, 200);
	}
	/**
	 * To change elementor text
	 */
	public function elementor_widget_render_content($content, $widget) {
		// echo sprintf('Hi there %s', $content);
		return $content;
	}
	public function isEnglish($string) {
		// Removing non-English characters
		$englishString = preg_replace('/[^A-Za-z0-9\s]/', '', $string);
		
		// Comparing the length of the original string and the English-only string
		if (strlen($string) === strlen($englishString)) {
			return true; // The string is in English
		} else {
			return false; // The string contains non-English characters
		}
	}
}
