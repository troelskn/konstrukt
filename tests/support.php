<?php
if (!class_exists('SimplePutEncoding')) {
	class SimplePutEncoding extends SimplePostEncoding {
		function getMethod() {
			return 'PUT';
		}
	}
}
if (!class_exists('SimpleDeleteEncoding')) {
	class SimpleDeleteEncoding extends SimpleGetEncoding {
		function getMethod() {
			return 'DELETE';
		}
	}
}

class ExtendedWebTestCase extends WebTestCase
{
	function ExtendedWebTestCase() {
		$this->WebTestCase();
		$this->_baseUrl = TestEnv::Get('test_server_url');
	}

	function request($method, $url, $parameters = FALSE) {
		if (!is_object($url)) {
			$url = new SimpleUrl($url);
		}
		if ($this->getUrl()) {
			$url = $url->makeAbsolute($this->_browser->getUrl());
		}
		switch (strtoupper($method)) {
			case 'GET' : $encoding = new SimpleGetEncoding($parameters);
			break;
			case 'POST' : $encoding = new SimplePostEncoding($parameters);
			break;
			case 'PUT' : $encoding = new SimplePutEncoding($parameters);
			break;
			case 'DELETE' : $encoding = new SimpleDeleteEncoding($parameters);
			break;
			default :
				$this->fail("Unknown method '$method'");
				return;
		}
		return $this->_browser->_load($url, $encoding);
	}
}
?>