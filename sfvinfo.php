<?php

require_once dirname(__FILE__).'/archivereader.php';

/**
 * SfvInfo class.
 *
 * A simple class for inspecting SFV file data and listing information about the
 * contents. Data can be streamed from a file or loaded directly from memory.
 *
 * Example usage:
 *
 * <code>
 *
 *   // Load the SFV file or data
 *   $sfv = new SfvInfo;
 *   $sfv->open('./foo.sfv'); // or $sfv->setData($data);
 *   if ($sfv->error) {
 *     echo "Error: {$sfv->error}\n";
 *     exit;
 *   }
 *
 *   // Process the file list
 *   $files = $sfv->getFileList();
 *   foreach ($files as $file) {
 *     echo $file['name'].' - '.$file['checksum'];
 *   }
 *
 * </code>
 *
 * @author     Hecks
 * @copyright  (c) 2010-2013 Hecks
 * @license    Modified BSD
 * @version    1.7
 */
class SfvInfo extends ArchiveReader
{
	/**
	 * SFV file comments.
	 * @var string
	 */
	public $comments = '';

	/**
	 * Convenience method that outputs a summary list of the SFV file records,
	 * useful for pretty-printing.
	 *
	 * @param   boolean  $basenames  don't include full file paths?
	 * @return  array    file record summary
	 */
	public function getSummary($basenames=false)
	{
		return array(
			'sfv_file'   => $this->file,
			'file_size'  => $this->fileSize,
			'data_size'  => $this->dataSize,
			'file_count' => $this->fileCount,
			'file_list'  => $this->getFileList($basenames),
		);
	}

	/**
	 * Returns a list of file records with checksums from the source SFV file.
	 *
	 * @param   boolean  $basenames  don't include full file paths?
	 * @return  mixed    false if no info is available, or array of file records
	 */
	public function getFileList($basenames=false)
	{
		if (empty($this->fileList))
			return false;

		if ($basenames) {
			$ret = array();
			foreach ($this->fileList as $item) {
				$item['name'] = pathinfo($item['name'], PATHINFO_BASENAME);
				$ret[] = $item;
			}
			return $ret;
		}

		return $this->fileList;
	}

	/**
	 * The parsed file list with checksum info.
	 * @var array
	 */
	protected $fileList = array();

	/**
	 * Parses the source data and stores a list of valid file records locally.
	 *
	 * @return  boolean  false if parsing fails
	 */
	protected function analyze()
	{
		// Get the available data up to the maximum allowed
		$data = $this->read(min($this->length, $this->maxReadBytes));

		// Split on all line ending types
		foreach (preg_split('/\R/', $data, -1, PREG_SPLIT_NO_EMPTY) as $line) {

			// Store comment lines
			if (strpos($line, ';') === 0) {
				$this->comments .= trim(substr($line, 1))."\n";
				continue;
			}

			if (preg_match('/^(.+?)\s+([[:xdigit:]]{2,8})$/', trim($line), $matches)) {

				// Store the file record locally
				$this->fileList[] = array(
					'name'     => $matches[1],
					'checksum' => $matches[2]
				);

				// Increment the filecount
				$this->fileCount++;

			} else {

				// Contains invalid chars, so assume this isn't an SFV
				$this->error = 'Not a valid SFV file';
				$this->close();
				return false;
			}
		}

		// Analysis was successful
		$this->close();
		return true;
	}

	/**
	 * Resets the instance variables before parsing new data.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		parent::reset();
		$this->fileList = array();
		$this->comments = '';
	}

} // End SfvInfo class
