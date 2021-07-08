<?php
namespace Piggly\Pix;

use Exception;

/**
 * The Pix Parser class.
 * 
 * This is used to parse and format data following patterns and 
 * standards of a pix.
 *
 * @since      1.0.0
 * @package    Piggly\Pix
 * @subpackage Piggly\Pix
 * @author     Caique <caique@piggly.com.br>
 */
class Parser
{
	/** @var string KEY_TYPE_RANDOM Random key. */
	const KEY_TYPE_RANDOM = 'random';
	/** @var string KEY_TYPE_DOCUMENT Document key. */
	const KEY_TYPE_DOCUMENT = 'document';
	/** @var string KEY_TYPE_EMAIL Email key. */
	const KEY_TYPE_EMAIL = 'email';
	/** @var string KEY_TYPE_PHONE Phone key. */
	const KEY_TYPE_PHONE = 'phone';

	/**
	 * Return the alias for key value.
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return string
	 */
	public static function getAlias ( string $key ) : string 
	{
		switch ( $key )
		{
			case self::KEY_TYPE_RANDOM:
				return 'Chave Aleatória';
			case self::KEY_TYPE_DOCUMENT:
				return 'CPF/CNPJ';
			case self::KEY_TYPE_EMAIL:
				return 'E-mail';
			case self::KEY_TYPE_PHONE:
				return 'Telefone';
		}

		return 'Chave Desconhecida';
	}

	/**
	 * Validate a $value based in the respective pix $key.
	 * 
	 * @since 1.0.0
	 * @param string $keyType Pix key type.
	 * @param string $value Pix key value.
	 * @throws Exception
	 */
	public static function validate ( string $keyType, string $keyValue )
	{
		if ( !in_array($keyType, [self::KEY_TYPE_RANDOM, self::KEY_TYPE_DOCUMENT, self::KEY_TYPE_EMAIL, self::KEY_TYPE_PHONE]) )
		{ throw new Exception(sprintf('A chave `%s` é desconhecida.', $keyType)); }

		$validate = false;
		$alias    = 'Chave Desconhecida';

		switch ( $keyType )
		{
			case self::KEY_TYPE_RANDOM:
				$validate = self::validateRandom($keyValue);
				$alias    = self::getAlias($keyType);
				break;
			case self::KEY_TYPE_DOCUMENT:
				$validate = self::validateDocument($keyValue);
				$alias    = self::getAlias($keyType);
				break;
			case self::KEY_TYPE_EMAIL:
				$validate = self::validateEmail($keyValue);
				$alias    = self::getAlias($keyType);
				break;
			case self::KEY_TYPE_PHONE:
				$validate = self::validatePhone($keyValue);
				$alias    = self::getAlias($keyType);
				break;
		}

		if ( !$validate )
		{ throw new Exception(sprintf('O valor `%s` para %s está inválido.', $alias, $keyValue)); }
	}

	/**
	 * Validates the random key value.
	 * 
	 * @since 1.0.0
	 * @param string $random Pix key value.
	 * @return bool
	 */
	public static function validateRandom ( string $random ) : bool
	{
		if ( !preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $random) )
		{ return false; }

		return true;
	}

	/**
	 * Validates the document key value.
	 * 
	 * @since 1.0.0
	 * @param string $document Pix key value.
	 * @return bool
	 */
	public static function validateDocument ( string $document ) : bool
	{
		$parsed = self::parseDocument($document);
		
		if ( strlen($parsed) === 11 )
		{ return self::validateCpf($parsed); }
		else if ( strlen($parsed) === 14 )
		{ return self::validateCnpj($parsed); }

		return false;
	}

	/**
	 * Validates a CPF number.
	 * 
	 * @since 1.0.0
	 * @param string $document String with only numbers.
	 * @return bool
	 */
	protected static function validateCpf ( string $document ) : bool
	{
		// Only numbers
		if ( !preg_match('/^[\d]{11}$/', $document) ) 
		{ return false; }
	
		// CPF Checksum
		for ($t = 9; $t < 11; $t++) 
		{
			for ( $d = 0, $c = 0; $c < $t; $c++ ) 
			{ $d += $document[$c] * (($t + 1) - $c); }

			$d = ((10 * $d) % 11) % 10;

			if ( $document[$c] != $d ) 
			{ return false; }
	  	}

		return true;
	}

	/**
	 * Validates a CNPJ number.
	 * 
	 * @since 1.0.0
	 * @param string $document String with only numbers.
	 * @return bool
	 */
	protected static function validateCnpj ( string $document ) : bool
	{
		// Only numbers
		if ( !preg_match('/^[\d]{14}$/', $document) ) 
		{ return false; }
	
		// CNPJ first Checksum
		for ( $i = 0, $j = 5, $sum = 0; $i < 12; $i++ )
		{
			$sum += $document[$i] * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}

		$result = $sum % 11;

		if ( $document[12] !== (string)( $result < 2 ? 0 : 11 - $result ) )
		{ return false; }
	
		// CNPJ second Checksum
		for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++)
		{
			$sum += $document[$i] * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}

		$result = $sum % 11;

		if ( $document[13] !== (string)( $result < 2 ? 0 : 11 - $result ) )
		{ return false; }

		return true;
	}

	/**
	 * Validates the email key value.
	 * 
	 * @since 1.0.0
	 * @param string $email Pix key value.
	 * @return bool
	 */
	public static function validateEmail ( string $email ) : bool
	{
		$email = str_replace(' ', '@', $email);
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Validates the phone key value.
	 * 
	 * @since 1.0.0
	 * @param string $phone Pix key value.
	 * @return bool
	 */
	public static function validatePhone ( string $phone ) : bool
	{
		$parsed = self::parsePhone($phone);

		if ( !preg_match('/^(\+55)?(\d{10,11})$/', $parsed) )
		{ return false; }

		return true;
	}

	/**
	 * Parse a $value based in the respective pix $key.
	 * 
	 * @since 1.0.0
	 * @param string $key Pix key.
	 * @param string $value Pix value.
	 * @return string
	 * @throws Exception
	 */
	public static function parse ( string $key, string $value ) : string
	{
		switch ( $key )
		{
			case self::KEY_TYPE_RANDOM:
				return $value;
			case self::KEY_TYPE_DOCUMENT:
				return self::parseDocument($value);
			case self::KEY_TYPE_EMAIL:
				return self::parseEmail($value);
			case self::KEY_TYPE_PHONE:
				return self::parsePhone($value);
		}

		throw new Exception(sprintf('A chave `%s` é desconhecida.', $key));
	}

	/**
	 * Parse any document string to a correct document format.
	 * 
	 * @since 1.0.0
	 * @param string $document
	 * @return string
	 */
	public static function parseDocument ( string $document ) : string
	{ return preg_replace('/[^\d]+/', '', $document); }

	/**
	 * Parse any email string to a correct email format.
	 * 
	 * @since 1.0.0
	 * @param string $email
	 * @param string $replaceAt replaces the @ character to space
	 * @return string
	 */
	public static function parseEmail ( string $email, bool $replaceAt = false ) : string
	{ 
		if ( !$replaceAt )
		{ return $email; }

		return str_replace('@', ' ', $email); 
	}

	/**
	 * Parse any phone string to a correct phone format.
	 * 
	 * @since 1.0.0
	 * @param string $phone
	 * @return string
	 */
	public static function parsePhone ( string $phone ) : string
	{
		$phone = str_replace('+55', '', $phone);
		$phone = preg_replace('/[^\d]+/', '', $phone);
		return '+55'.$phone;
	}

	/**
	 * Parse transaction id string to valid characters.
	 * 
	 * @since 1.1.2
	 * @param string $phone
	 * @return string
	 */
	public static function parseTid ( string $tid = null )
	{
		if ( empty($tid) )
		{ return '***'; }

		return preg_replace('/[^A-Za-z0-9]+/', '', $tid);
	}

	/**
	 * Return the key type based in the pix key.
	 * 
	 * @since 1.1.0
	 * @param string $pixKey
	 * @return string
	 * @throws Exception When an invalid type is found.
	 */
	public static function getKeyType ( string $pixKey ) : string
	{
		// Valid uuid-v4
		if ( self::validateRandom($pixKey) )
		{ return self::KEY_TYPE_RANDOM; }

		// Valid e-mail
		if ( self::validateEmail($pixKey) )
		{ return self::KEY_TYPE_EMAIL; }

		// Valid CPF or CNPJ
		if ( self::validateCpf($pixKey) || self::validateCnpj($pixKey) )
		{ return self::KEY_TYPE_DOCUMENT; }

		// Any type of phone
		if ( self::validatePhone($pixKey) )
		{ return self::KEY_TYPE_PHONE; }

		throw new Exception(sprintf('Não é possível determinar o tipo da chave `%s`', $pixKey));
	}
}