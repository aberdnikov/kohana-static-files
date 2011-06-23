<?php defined('SYSPATH') or die('No direct script access.');

class File extends Kohana_File {

	/**
	 * Removes directory and(or only) entire folder content
	 *
	 * @static
	 * @throws Kohana_Exception
	 * @param string $dir_name
	 * @param bool   $entire_only If set clears only entire folder content (folders and files inside)
	 * @return void
	 */
	public static function rmdir($dir_name, $entire_only = FALSE)
	{
		if (is_dir($dir_name))
		{
			$objects = scandir($dir_name);

			foreach ($objects as $object)
			{
				if ($object != '.' AND $object != '..')
				{
					$object_inside = $dir_name . DIRECTORY_SEPARATOR . $object;

					if (filetype($object_inside) == 'dir')
					{

						self::rmdir($object_inside);
					}
					else
					{
						if( ! unlink($object_inside))
						{
							throw new Kohana_Exception('can\'t remove object ' . $object_inside);
						}
					}
				}
			}

			reset($objects);

			if($entire_only === FALSE)
			{
				if( ! rmdir($dir_name))
				{
					throw new Kohana_Exception('can\'t remove directory ' . $dir_name);
				}
			}
		}
	}
}