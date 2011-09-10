<?php
/**
 * @author Matthias Tylkowski
 */

// get all input arguments starting from first, 0 is function call itself
$mpInputFiles = array_slice($argv, 1);

// if there are no arguments print usage information
if(count($mpInputFiles) >= 1){
	$oConverter = new Converter();
	$oConverter->convert($mpInputFiles);
	$oConverter->save();
}else{
	print "Usage: php convertPHPMyAdminXML2UnitTestXML.CLI.php  filename1 filename2 ... > path to file to create";
}

class Converter{
	private $oDom;
	private $oRootNode;
	
	// temp variables
	private $sCurrentTable;
	private $oTmpTable;
	
	public function __construct(){
		// setup dom with root node
		$this->oDom = new DOMDocument('1.0', 'utf-8');
		$this->oRootNode = $this->oDom->createElement('dataset');
	}
	
	public function convert(array $mpInputFiles){
		// run through all input arguments, create a temp dom and load the files
		foreach($mpInputFiles as $sInputFileName){
			$oTmpDom = new DOMDocument();
			$oTmpDom->load($sInputFileName);
			
			// get all <table> elements
			$oNodeList = $oTmpDom->getElementsByTagName('table');

			foreach($oNodeList as $oNode){
				// ignore table elements that are in the pma namespace
				if(strpos($oNode->nodeName, 'pma') === false){
					// store the table name 
					$sCurrentTable = $oNode->getAttribute('name');

					// if no table was set or current table name differs from stored one
					if(is_null($this->sCurrentTable) || $sCurrentTable !== $this->sCurrentTable) {
						// if there was already a stored table name append the table to the root node
						if($sCurrentTable !== $this->sCurrentTable && !is_null($this->sCurrentTable)){
							$this->oRootNode->appendChild($this->oTmpTable);
						}

						// create a new table and set its name
						$this->sCurrentTable = $sCurrentTable;
						$this->oTmpTable = $this->oDom->createElement('table');
						$this->oTmpTable->setAttribute('name', $this->sCurrentTable);

						// fetch all column names from the table
						$this->defineColumns($oNode);
					}

					$this->formatTableData($oNode);
				}
			}
		}
		
		$this->oRootNode->appendChild($this->oTmpTable);
		$this->oDom->appendChild($this->oRootNode);
	}
	
	/**
	 * collect all column elements and convert them to rows with column values
	 * @param DOMNode $oNode 
	 */
	protected function formatTableData(DOMNode $oNode){
		$oRow = $this->oDom->createElement('row');
		foreach($oNode->getElementsByTagName('column') as $oElement){
			$tmpElement = $this->oDom->createElement('value');
			// create temp text node to have escaped values
			$tmpTextNode = $this->oDom->createTextNode($oElement->nodeValue);
			
			$tmpElement->appendChild($tmpTextNode);
			$oRow->appendChild($tmpElement);
		}
		$this->oTmpTable->appendChild($oRow);
	}
	
	/**
	 * collect all columns from the current table and convert them to another format
	 * @param DOMNode $oNode 
	 */
	protected function defineColumns(DOMNode $oNode){
		foreach($oNode->getElementsByTagName('column')as $oElement){
			$tmpElement = $this->oDom->createElement('column', $oElement->getAttribute('name'));
			$this->oTmpTable->appendChild($tmpElement);
		}
	}
	
	/**
	 * send the xml string to stdout
	 * @return string
	 */
	public function save(){
		return $this->oDom->save('php://stdout');
	}
}

/**
 * Helper function for printing DOMNodes
 * @param DOMNode $node
 * @return string
 */
function dump_child_nodes($node)
{
  $output = '';
  $owner_document = $node->ownerDocument;
   
  foreach ($node->childNodes as $el){
    $output .= $owner_document->saveXML($el);
  }
  return $output;
}