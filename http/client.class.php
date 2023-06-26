<?php

namespace oTools\http;

class client
{
	protected static $code;

	public static function code()
	{
		return (int)self::$code;
	}

	/**
	 * Fonction qui permet de mettre à jour un fichier à partir d'une source HTTP
	 * uniquement si la source a changer. Si la source n'a pas changée, rien n'est téléchargé.
	 * $url (string) : une URL http.
	 * $path (string) : un chemin de fichier cible local.
	 * $destination (string) : facultatif un chenmin pour le nouveau fichier, si null prend la valeur de $path
	 *
	 * retourne : true si le fichier a été modifier, false sinon
	 *
	 * Exceptions:
	 * 1 : la cible existe et n'est pas un fichier (répertoire portant le même nom qu'un fichier ?)
	 * 2 : Impossible de créer le fichier temporaire (problême de droits ?)
	 * 3 : Code de retour HTTP non attendu. (la source n'existe pas ?)
	 **/
	public static function update($url,$path,$destination = null)
	{
		if (is_null($destination))
			$destination = $path;
		$tmp_path = $path.'.'.uniqid();					 // génération d'un nom temporaire unique
		while (file_exists($tmp_path))					  // tant que le nom existe déjé en générer un nouveau
			$tmp_path = $path.'.'.uniqid();
		if (($fh = fopen($tmp_path,'w')) !== false)		 // ouverture du ficher temporaire en écriture
		{
			$ch = curl_init($url);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
			curl_setopt($ch,CURLOPT_MAXREDIRS,3);
			curl_setopt($ch,CURLOPT_FILE,$fh);
			if (is_file($path))							 // si le fichier cible existe utilisé le "modified time"
			{
				curl_setopt($ch,CURLOPT_TIMECONDITION,CURL_TIMECOND_IFMODSINCE);
				curl_setopt($ch,CURLOPT_TIMEVALUE,filemtime($path));
			}
			curl_exec($ch);
			fclose($fh);
			$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);   // récupération du code de retour HTTP
			if ($code === 304)							  // si non modifié, effacer le fichier temporaire (vide)
				unlink($tmp_path);
			elseif ($code === 200)						  // si modifié remplacer le fichier cible
			{
				if (is_file($destination))						 // la cible existe déjà et est un fichier : effacement
					unlink($destination);
				elseif (file_exists($destination))				 // la cible existe et n'est pas un fichier : exception
					throw new exception('"%s" n\'est pas un fichier.',$destination);
				rename($tmp_path,$destination);
			}
			else											// autres codes HTTP non attendus : exception
				throw new exception('Code HTTP %d non attendu.',$code);
			curl_close($ch);
			return ($code === 200);
		}
		else												// ouverture du fichier temporaire a échouée : exception
			throw new exception('Impossible de créer le fichier \'%s\' en écriture.',$tmp_path);
	}

	public static function resolvUrl($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($ch,CURLOPT_MAXREDIRS,3);
		curl_setopt($ch,CURLOPT_NOBODY,true);
		curl_exec($ch);
		return curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
	}

	public static function get($url,$headers = array(),$follow_location = true,int $timeout = 5)
	{
		$handle = curl_init($url);
		curl_setopt($handle,CURLOPT_CONNECTTIMEOUT,$timeout);
		curl_setopt($handle,CURLOPT_FOLLOWLOCATION,$follow_location);
		curl_setopt($handle,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($handle,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($handle,CURLOPT_HEADER,false);
		$response = curl_exec($handle);
		self::$code = curl_getinfo($handle,CURLINFO_HTTP_CODE);
		if(curl_errno($handle))
			throw new exception('Curl: '.curl_error($handle));
		curl_close($handle);
		return $response;
	}

	public static function post($url,$data,$headers = array(),$follow_location = true,int $timeout = 5)
	{
		$handle = curl_init($url);
		curl_setopt($handle,CURLOPT_CONNECTTIMEOUT,$timeout);
		curl_setopt($handle,CURLOPT_FOLLOWLOCATION,$follow_location);
		curl_setopt($handle,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($handle,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($handle,CURLOPT_HEADER,false);
		curl_setopt($handle,CURLOPT_POST,true);
		curl_setopt($handle,CURLOPT_POSTFIELDS,$data);
		$response = curl_exec($handle);
		self::$code = curl_getinfo($handle,CURLINFO_HTTP_CODE);
		if(curl_errno($handle))
			throw new exception('Curl: '.curl_error($handle));
		curl_close($handle);
		return $response;
	}

	public static function put($url,$data,$headers = array(),$follow_location = true)
	{
		$headers[] = sprintf('Content-Length: %d',strlen($data));
		$handle = curl_init($url);
		curl_setopt($handle,CURLOPT_CUSTOMREQUEST,'PUT');
		curl_setopt($handle,CURLOPT_FOLLOWLOCATION,$follow_location);
		curl_setopt($handle,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($handle,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($handle,CURLOPT_HEADER,false);
		curl_setopt($handle,CURLOPT_POSTFIELDS,$data);
		$response = curl_exec($handle);
		self::$code = curl_getinfo($handle,CURLINFO_HTTP_CODE);
		if(curl_errno($handle))
			throw new exception('Curl: '.curl_error($handle));
		curl_close($handle);
		return $response;
	}
}
