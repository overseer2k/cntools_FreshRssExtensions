<?php
class FeedTitleBuilderExtension extends Minz_Extension {
	public static $feedUrl = '';

	public function init() {
		self::$feedUrl = '';
		$this->registerHook('feed_before_insert', array('FeedTitleBuilderExtension', 'CntBuildTitle'));
	}

	public function handleConfigureAction() {
		$this->registerTranslates();

		if (Minz_Request::isPost()) {
			FreshRSS_Context::$user_conf->feedtitlebuilder_template = Minz_Request::param('feedtitlebuilder_template', '');
			FreshRSS_Context::$user_conf->save();
		}
	}

	public static function CntBuildTitle($feed) {
		if (is_object($feed) === true) {
			$lWorkTitle = FreshRSS_Context::$user_conf->feedtitlebuilder_template;
			if ($lWorkTitle == '') {
				// do nothing
			} else {
				// place original title
				$lWorkTitle = str_replace('{origtitle}', $feed->name(), $lWorkTitle);

				// transfer url variables
				self::$feedUrl = $feed->url();
				$regex = "#{url\b(.*?)\}(.*?){/url}#s";
				$lWorkTitle = preg_replace_callback($regex, array('FeedTitleBuilderExtension', 'renderTitleBuilderPhpParseUrl'), $lWorkTitle);

				// transfer date variables
				$regex = "#{date\b(.*?)\}(.*?){/date}#s";
				$lWorkTitle = preg_replace_callback($regex, array('FeedTitleBuilderExtension', 'renderTitleBuilderDate'), $lWorkTitle);

				// send new title to feed
				$feed->_name($lWorkTitle);
			}
		}
		return $feed;
	}

	/*---------------------------- renderTitleBuilderPhpParseUrl ----------------------------*/
	private function renderTitleBuilderPhpParseUrl(&$matches){
		$urlValues = parse_url(self::$feedUrl);

		$hostValues = explode('.', $urlValues['host']);
		$urlValues['hosttld'] = array_pop($hostValues);
		$urlValues['hostname'] = array_pop($hostValues);
		$urlValues['hostsub'] = implode('.', $hostValues);

		return $urlValues[$matches[2]];
	}

	/*---------------------------- renderTitleBuilderDate ----------------------------*/
	private function renderTitleBuilderDate(&$matches){
		return date(trim($matches[2]));
	}
	
}