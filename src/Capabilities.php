<?php namespace PHRETS;

class Capabilities
{
    protected $capabilities = [];

    /**
     * @param $name
     * @param $uri
     * @return $this
     */
    public function add($name, $uri)
    {
        $parse_results = parse_url($uri);
        if (!array_key_exists('host', $parse_results) or !$parse_results['host']) {
            // relative URL given, so build this into an absolute URL
            $login_url = $this->get('Login');
            if (!$login_url) {
                throw new \InvalidArgumentException("Cannot automatically determine absolute path for '{$uri}' given");
            }

            $parts = parse_url($login_url);

            $new_uri = $parts['scheme'] . '://' . $parts['host'] . ':';
            $new_uri .= (empty($parts['port'])) ? (($parts['scheme'] == 'https') ? 443 : 80) : $parts['port'];
            $new_uri .= $uri;

            $uri = $new_uri;
        }

        $this->capabilities[$name] = $uri;
        return $this;
    }

    /**
     * @param $name
     * @return null
     */
    public function get($name)
    {
        return (array_key_exists($name, $this->capabilities)) ? $this->capabilities[$name] : null;
    }
}
