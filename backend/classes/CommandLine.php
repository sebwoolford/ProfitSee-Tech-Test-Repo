<?php

final class CommandLine
{
	
	/**
	 * An array of required command line arguments
	 */
	private $_arguments = array();
	
	
	/**
	 * An array containing the actual arguments that were supplied
	 */
	private $_suppliedArguments = array();
	
	
	/**
	 * Class constructor. Can optionally specificy arguments when instantiating
	 * or you can use addArgument(). Also sets the supplied arguments.
	 *
	 * @param array|null $arguments  an optional array of command line arguments
	 */
	public function __construct($arguments = null)
	{
		// Assign the supplied command line arguments to $_suppliedArguments
		if(is_array($_SERVER['argv']))
		{
			$this->_suppliedArguments = $_SERVER['argv'];
		}
		
		// If arguments supplied, add them
		if(is_array($arguments))
		{
			foreach($arguments as $name)
			{
				$this->addArgument($name);
			}
		}
	}
	
	
	/**
	 * Add an argument
	 *
	 * @param string $argument  An argument name, so that we can refer to the argument
	 *                          easily., eg $CommandLine->companyId
	 */
	public function addArgument($argument)
	{
		if(is_string($argument))
		{
			$this->_arguments[] = $argument;
		}
		else
		{
			throw new Exception('Error. Supplied argument name must be a string.');
		}
		
		return $this;
	}
	
	
	/**
	 * Output a string telling the user the usage/argument names for the script
	 *
	 * @return CommandLine  The current object for method chaining.
	 */
	public function showUsage()
	{
		$usage = 'Usage: php ' . $_SERVER['SCRIPT_FILENAME'];
		
		foreach($this->_arguments as $argument)
		{
			$usage .= " [{$argument}]";
		}
		$this->outputErrorLine($usage);
		return $this;
	}
	
	
	/**
	 * Process the supplied arguments and run some checks.
	 *
	 * @return CommandLine  The current object for method chaining.
	 */
	public function process()
	{
		// Ensure the script is being called via the command line
		if (PHP_SAPI !== 'cli')
		{
			$this
				->outputLine('<h1>Error. This script must be called via the CLI.</h1>')
				->abort();
		}
		
		// Ensure the correct number of arguments have been supplied
		if( (count($this->_suppliedArguments) - 1) !== count($this->_arguments) )
		{
			$this
				->outputErrorLine('Error. Incorrect number of arguments supplied.')
				->showUsage()
				->abort();
		}
		
		// Assign each argument as a property
		$i = 1;
		foreach($this->_arguments as $argument)
		{
			$this->$argument = $this->_suppliedArguments[$i];
			$i++;
		}
		
		return $this;
	}
	
	
	/**
	 * Pause the script execution and get some input from the user.
	 *
	 * @param string $message  The message to show the user via the command line.
	 *
	 * @return string  The user input.
	 */
	public function getUserInput($message)
	{
		$this->outputLine($message);
		$line = fgets(STDIN);
		return trim($line);
	}
	
	
	/**
	 * Output a line to STDOUT.
	 *
	 * @param string $text  The text to output.
	 */
	public function outputLine($text='')
	{
		fwrite(STDOUT, "{$text}\n");
		return $this;
	}
	
	
	/**
	 * Output a line to STDERR.
	 *
	 * @param string $text  The text to output.
	 */
	public function outputErrorLine($text='')
	{
		fwrite(STDERR, "{$text}\n");
		return $this;
	}
	
	
	/**
	 * Stop script execution.
	 */
	public function abort()
	{
		// Exit with a status code so the OS knows it failed
		exit(1);
	}
	
	
}

?>