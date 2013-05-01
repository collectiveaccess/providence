<?php
/**
 * PHP CLI Progress bar
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2011, Andy Dawson
 * @link          http://ad7six.com
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * ProgressBar 
 *
 * Static wrapper class for generating progress bars for cli tasks
 * 
 */
class CLIProgressBar
{

    /**
     * Merged with options passed in start function 
     */
    protected static $defaults = array(
        'format' => "\r:message::padding:%.01f%% %2\$d/%3\$d ETC: %4\$s. Elapsed: %5\$s [%6\$s]",
        'ncursesFormat' => ":message::padding:%.01f%% %2\$d/%3\$d ETC: %4\$s. Elapsed: %5\$s [%6\$s]",
        'message' => 'Running',
        'size' => 30,
        'width' => null
    );

    /**
     * Runtime options 
     */
    protected static $options = array();

    /**
     * How much have we done already 
     */
    protected static $done = 0;

    /**
     * The format string used for the rendered status bar - see $defaults
     */
    protected static $format;

    /**
     * message to display prefixing the progress bar text
     */
    protected static $message;

    /**
     * How many chars to use for the progress bar itself. Not to be confused with $width
     */
    protected static $size = 30;

    /**
     * When did we start (timestamp)
     */
    protected static $start;

    /**
     * The width in characters the whole rendered string must fit in. defaults to the width of the 
     * terminal window
     */
    protected static $width;

    /**
     * What's the total number of times we're going to call set 
     */
    protected static $total;
    
    /**
     * Optional ncurses window to render progress bar into
     */
    protected static $window = null;

    /**
     * Show a progress bar, actually not usually called explicitly. Called by next()
     * 
     * @param int $done what fraction of $total to set as progress uses internal counter if not passed
     * 
     * @static
     * @return string, the formatted progress bar prefixed with a carriage return
     */
    public static function display($done = null)
    {
        if ($done) {
            self::$done = $done;
        }

        $now = time();

        if (self::$total) {
            $fractionComplete = (double) (self::$done / self::$total);
        } else {
            $fractionComplete = 0;
        }

        $bar = floor($fractionComplete * self::$size);
        $barSize = min($bar, self::$size);

        $barContents = str_repeat('=', $barSize);
        if ($bar < self::$size) {
            $barContents .= '>';
            $barContents .= str_repeat(' ', self::$size - $barSize);
        } elseif ($fractionComplete > 1) {
            $barContents .= '!';
        } else {
            $barContents .= '=';
        }

        $percent = number_format($fractionComplete * 100, 0);

        $elapsed = $now - self::$start;
        if (self::$done) {
            $rate = $elapsed / self::$done;
        } else {
            $rate = 0;
        }
        $left = self::$total - self::$done;
        $etc = round($rate * $left, 2);

        if (self::$done) {
            $etcNowText = '< 1 sec';
        } else {
            $etcNowText = '???';
        }
        $timeRemaining = self::humanTime($etc, $etcNowText);
        $timeElapsed = self::humanTime($elapsed);

        $return = sprintf(
            self::$format,
            $percent,
            self::$done,
            self::$total,
            $timeRemaining,
            $timeElapsed,
            $barContents
        );

        $width = strlen(preg_replace('@(?:\r|:\w+:)@', '', $return));

        if (strlen(self::$message) > (self::$width - $width - 3)) {
            $message = substr(self::$message, 0, (self::$width - $width - 4)) . '...';
            $padding = '';
            echo "\n" . strlen($return);
        } else {
            $message = self::$message;
            $width += strlen($message);
            $padding = str_repeat(' ', (self::$width - $width));
        }

        $return = str_replace(':message:', $message, $return);
        $return = str_replace(':padding:', $padding, $return);

		if (self::$window) {
			ncurses_mvwaddstr(self::$window, 1, 2, $return);
			ncurses_refresh();
			ncurses_wrefresh(self::$window);
			return '';
		}
        return $return;
    }

    /**
     * reset internal state, and send a new line so that the progress bar text is "finished"
     * 
     * @static
     * @return string, a new line
     */
    public static function finish()
    {
        self::reset();
        if (self::$window) {
        	ncurses_mvwaddstr(self::$window, 1, 2, "\n");
			ncurses_refresh();
			ncurses_wrefresh(self::$window);
        }
        return "\n";
    }

    /**
     * Increment the internal counter, and returns the result of display
     * 
     * @param int    $inc     Amount to increment the internal counter
     * @param string $message If passed, overrides the existing message
     *
     * @static
     * @return string - the progress bar
     */
    public static function next($inc = 1, $message = '')
    {
        self::$done += $inc;

        if ($message) {
            self::$message = $message;
        }

        return self::display();
    }

    /**
     * Called by start and finish
     * 
     * @param array $options array
     *
     * @static
     * @return void
     */
    public static function reset($options = array())
    {
        $options = array_merge(self::$defaults, $options);

        if (empty($options['done'])) {
            $options['done'] = 0;
        }
        if (empty($options['start'])) {
            $options['start'] = time();
        }
        if (empty($options['total'])) {
            $options['total'] = 0;
        }
        
        self::$window =  $options['window'];

        self::$done = $options['done'];
        self::$format =  (!self::$window) ? $options['format'] : $options['ncursesFormat'];
        self::$message = CLIProgressBar::stripReturns($options['message']);
        self::$size = $options['size'];
        self::$start = $options['start'];
        self::$total = $options['total'];
        self::setWidth($options['width']);
    }

	/**
     * 
     */
	public static function stripReturns($text) {
		return preg_replace('![\r\n\t]+!', ' ', $text);
	}
	
    /**
     * change the message to be used the next time the display method is called
     * 
     * @param string $message the string to display
     *
     * @static
     * @return void
     */
    public static function setMessage($message = '')
    {
        self::$message = CLIProgressBar::stripReturns($message);
    }

    /**
     * change the total on a running progress bar
     * 
     * @param int $total the new number of times we're expecting to run for
     *
     * @static
     * @return void
     */
    public static function setTotal($total = '')
    {
        self::$total = $total;
    }

    /**
     * Initialize a progress bar
     * 
     * @param mixed $total   number of times we're going to call set
     * @param int   $message message to prefix the bar with
     * @param int   $options overrides for default options
     * 
     * @static
     * @return string - the progress bar string with 0 progress
     */
    public static function start($total = null, $message = '', $options = array())
    {
        if ($message) {
            $options['message'] = CLIProgressBar::stripReturns($message);
        }
        $options['total'] = $total;
        $options['start'] = time();
        self::reset($options);

        return self::display();
    }

    /**
     * Convert a number of seconds into something human readable like "2 days, 4 hrs"
     * 
     * @param int    $seconds how far in the future/past to display
     * @param string $nowText if there are no seconds, what text to display
     *
     * @static
     * @return string representation of the time
     */
    protected static function humanTime($seconds, $nowText = '< 1 sec')
    {
        $prefix = '';
        if ($seconds < 0) {
            $prefix = '- ';
            $seconds = -$seconds;
        }

        $days = $hours = $minutes = 0;

        if ($seconds >= 86400) {
            $days = (int) ($seconds / 86400);
            $seconds = $seconds - $days * 86400;
        }
        if ($seconds >= 3600) {
            $hours = (int) ($seconds / 3600);
            $seconds = $seconds - $hours * 3600;
        }
        if ($seconds >= 60) {
            $minutes = (int) ($seconds / 60);
            $seconds = $seconds - $minutes * 60;
        }
        $seconds = (int) $seconds;

        $return = array();

        if ($days) {
            $return[] = "$days days";
        }
        if ($hours) {
            $return[] = "$hours hrs";
        }
        if ($minutes) {
            $return[] = "$minutes mins";
        }
        if ($seconds) {
            $return[] = "$seconds secs";
        }

        if (!$return) {
            return $nowText;
        }
        return $prefix . implode(array_slice($return, 0, 2), ', ');
    }

    /**
     * Set the width the rendered text must fit in
     * 
     * @param int $width passed in options
     *
     * @static
     * @return void
     */
    protected static function setWidth($width = null)
    {
        if ($width === null) {
        	if (self::$window) {
        		ncurses_getmaxyx(self::$window, $vn_max_y, $vn_max_x);
        		$width = $vn_max_x - 4;
        	} else {
				if (DIRECTORY_SEPARATOR === '/') {
					$width = `tput cols`;
				}
			}
            if ($width < 80) {
                $width = 80;
            }
        }
        self::$width = $width;
    }
}
