<?php
# ---------------------------------------------------------------------
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
require_once(__CA_LIB_DIR__."/Search/ObjectSearch.php");
# ---------------------------------------------------------------------
function media_import($ps_xlsx, $ps_dir, $pb_uuid=false) {
	if(!mmsMediaAndArchiveDirsAreWritable()){
		mmsCritError("Medien [{$ps_xlsx}]: Der Nutzer, mit dem der Import gestartet wurde, kann auf mindestens ein Medien- oder Archivverzeichnis nicht schreibend zugreifen. Breche import ab. Für weitere Infos siehe Logfile.");
	}

	$t_locale = new ca_locales();
	if(!($vn_locale_id = $t_locale->localeCodeToID(__LHM_MMS_DEFAULT_LOCALE__))){
		CLIUtils::addError("Invalid locale code ".__LHM_MMS_DEFAULT_LOCALE__);
		return false;
	}

	$t_object = new ca_objects();
	$o_db = new Db();

	$vn_rows = count_nonempty_rows($ps_xlsx) - 1;

	$o_excel = phpexcel_load_file($ps_xlsx);
	$o_sheet = $o_excel->getActiveSheet();

	mmsLog("Medien [{$ps_xlsx}]: Verarbeite {$vn_rows} Zeilen ...", Zend_Log::INFO);
	print CLIProgressBar::start($vn_rows, "Verarbeite Medien ...");

	foreach ($o_sheet->getRowIterator() as $o_row) {
		$vn_row_num = $o_row->getRowIndex();
		if($vn_row_num == 1) continue; // headers

		mmsLog("Medien [{$ps_xlsx}]: Verarbeite Zeile {$vn_row_num} ...", Zend_Log::DEBUG);

		// Finde zuzuordnende Objekte ... je nach Modus via UUID Suche oder aus der Objekt-ID Spalte
		if($pb_uuid) { // UUID
			$vs_uuid = trim((string)$o_sheet->getCellByColumnAndRow(1, $vn_row_num));
			$o_search = new ObjectSearch();
			$o_result = $o_search->search('ca_objects.uuid:"'.$vs_uuid.'"', array('no_cache' => true, 'dontFilterByACL' => true));
			if($o_result->numHits()<1) {
				mmsLog("Medien [{$ps_xlsx}]: Konnte UUID '$vs_uuid' für Zeile $vn_row_num nicht mindestens einem Objekt zuordnen. Zeile wird übersprungen.", Zend_Log::WARN);
				print CLIProgressBar::next();
				continue;
			}

			$va_objects = array();
			while($o_result->nextHit()) {
				$va_objects[] = $o_result->get('object_id');
			}
		} else { // Einfaches ObjektID Mapping
			$va_objects = array(trim((string)$o_sheet->getCellByColumnAndRow(1, $vn_row_num)));
		}

		/// Neues Medium erstellen
		$t_rep = new ca_object_representations();
		$t_rep->setMode(ACCESS_WRITE);

		// Dateipfad + Dateiname
		$vs_file = trim((string)$o_sheet->getCellByColumnAndRow(2, $vn_row_num));
		$vs_local_path = mmsGetRealPath($ps_dir.DIRECTORY_SEPARATOR.$vs_file);

		// Medientyp
		$vs_type = trim((string)$o_sheet->getCellByColumnAndRow(3, $vn_row_num));
		$vs_type = mmsGetListItemIDByLabel('object_representation_types',$vs_type);

		if(!$vs_type) {
			mmsLog("Medien [{$ps_xlsx}]: Konnte Typ nicht bestimmen für Eintrag in Zeile $vn_row_num. Zeile wird übersprungen.", Zend_Log::WARN);
			print CLIProgressBar::next();
			continue;
		}

		// Status
		$vs_status = trim((string)$o_sheet->getCellByColumnAndRow(4, $vn_row_num));
		$vs_status = mmsGetListItemValueByLabel('workflow_statuses',$vs_status);

		if(!$vs_status) {
			mmsLog("Medien [{$ps_xlsx}]: Konnte Status nicht bestimmen für Eintrag in Zeile $vn_row_num. Zeile wird übersprungen.", Zend_Log::WARN);
			print CLIProgressBar::next();
			continue;
		}

		if(!$vs_local_path) {
			mmsLog("Medien [{$ps_xlsx}]: Datei '$vs_file' für Zeile $vn_row_num kann nicht gelesen werden oder existiert nicht; Zeile wird übersprungen.", Zend_Log::WARN);
			print CLIProgressBar::next();
			continue;
		}

		// Ist die Datei bereits vorhanden?
		$vs_md5 = md5_file($vs_local_path);
		$qr_md5 = $o_db->query("SELECT representation_id FROM ca_object_representations WHERE md5=? AND deleted=0", $vs_md5);
		if($qr_md5->numRows() > 0) { // Ja, ist vorhanden
			$qr_md5->nextRow(); // Invariante sollte sein: nur 1 Ergebnis ... falls nicht neben wir eben nur das erste
			mmsLog("Medien [{$ps_xlsx}]: Datei '$vs_local_path' in Zeile $vn_row_num scheint bereits in der Datenbank vorhanden zu sein. Wir nutzen nun den bereits vorhandenen Mediendatensatz zur Zuordnung.", Zend_Log::WARN);

			if($qr_md5->numRows() > 1) {
				mmsLog("Medien [{$ps_xlsx}]: Datei '$vs_local_path' für Zeile $vn_row_num scheint mehrfach in der Datenbank vorhanden zu sein. Wir nehmen uns das erste Ergebnis was uns der Datenbankserver gibt.", Zend_Log::WARN);
			}

			$t_rep->load($qr_md5->get('representation_id'));
		} else { // nein, erstelle neues Medium
			$t_rep->set('type_id', $vs_type);
			$t_rep->set('status', $vs_status);
			$t_rep->set('access', 0);
			$t_rep->set('media', $vs_local_path, array('original_filename' => $vs_file));
			$t_rep->insert();

			if($t_rep->numErrors() > 0) {
				foreach ($t_rep->getErrors() as $vs_error) {
					mmsLog("Medien [{$ps_xlsx}]: Neuanlegen von Mediendatensatz für Datei $vs_local_path in Zeile $vn_row_num schlug fehl. API Nachricht: {$vs_error}", Zend_Log::ERR);
				}
				if($t_rep->getPrimaryKey()) {
					$t_rep->delete(true, array('hard' => true));
				}
			} else {
				mmsLog("Medien [{$ps_xlsx}]: Import von $vs_local_path in Zeile $vn_row_num erfolgreich.", Zend_Log::INFO);
			}
		}

		if($vn_rep_id = $t_rep->getPrimaryKey()) { // Wenn ein Medium geladen ist (sollte immer der Fall sein, aber sicher ist sicher)

			// Füge Label hinzu falls noch nicht vorhanden
			if(strlen($t_rep->getLabelForDisplay()) < 1) {
				$t_rep->addLabel(array(
					'name' => '[LEER]',
				),$vn_locale_id, null, true);
			}

			if($t_rep->numErrors() > 0) {
				foreach ($t_rep->getErrors() as $vs_error) {
					mmsLog("Medien [{$ps_xlsx}]: Hinzufügen von Label zu Mediendatensatz schlug fehl. Zeile: {$vn_row_num}, Mediendatensatz ID:".$t_rep->getPrimaryKey().", API Nachricht: {$vs_error}. Der Datensatz ist nun eventuell unvollständig (Titel/original_filename).", Zend_Log::ERR);
				}
			}

			foreach($va_objects as $vn_object_id) {

				if(!$t_object->load($vn_object_id)) {
					mmsLog("Medien [{$ps_xlsx}]: Konnte zuzuordnendes Objekt mit ID '$vn_object_id' für Tabellenzeile $vn_row_num / Datei $vs_local_path nicht laden, das Objekt wird uebersprungen.", Zend_Log::ERR);
					continue;
				}

				if($vn_primary_rep = $t_object->getPrimaryRepresentationID()) { // if it's not the first rep, check the rep type of the old primary. we override in a number of cases
					// note that there's code in BaseRepresentationRelationship that changes
					// other reps to non-primary if a new primary is inserted for an objects,
					// i.e. we don't have to worry about that part
					$vn_primary = 0;
					$t_primary_rep = new ca_object_representations($vn_primary_rep);
					if($t_primary_rep->getPrimaryKey()) {
						switch($t_primary_rep->getTypeCode()) {
							case 'work_photo': // in this case we change the primary only if we're an image and the previous one is a work photo
								if($t_rep->getTypeCode() == 'image') {
									$vn_primary = 1;
								}
								break;
							case 'image': // image always wins, even if we're an image (i.e. we don't change)
								break;
							default: // if the previous primary is whatever, we change it if we're an image or a work photo
								if(in_array($t_rep->getTypeCode(), array('image', 'work_photo'))) {
									$vn_primary = 1;
								}
								break;
						}
					}
				} else { // this means this is the first rep, i.e. we always go primary
					$vn_primary = 1;
				}

				$va_rep_ids = array_keys($t_object->getRepresentationIDs());
				if(in_array($vn_rep_id, $va_rep_ids)) {
					mmsLog("Medien [{$ps_xlsx}]: Zeile {$vn_row_num} /  Pfad {$vs_local_path}: Objekt {$vn_object_id} und Medium {$vn_rep_id} sind bereits verbunden. Überspringe Objekt.", Zend_Log::WARN);
					continue;
				}

				$t_oxor = new ca_objects_x_object_representations();
				$t_oxor->setMode(ACCESS_WRITE);

				$t_oxor->set('object_id', $vn_object_id);
				$t_oxor->set('representation_id', $vn_rep_id);
				$t_oxor->set('is_primary', $vn_primary);
				$t_oxor->insert();

				if($t_oxor->numErrors() > 0) {
					foreach ($t_oxor->getErrors() as $vs_error) {
						mmsLog("Medien [{$ps_xlsx}]: Zeile {$vn_row_num} /  Pfad {$vs_local_path}: Verbinden von Objekt {$vn_object_id} und Medium {$vn_rep_id} schlug fehl. Überspringe Objekt. API Nachricht: {$vs_error}", Zend_Log::WARN);
					}
					$t_oxor->delete();
					continue;
				} else {
					mmsLog("Medien [{$ps_xlsx}]: Zeile {$vn_row_num} /  Pfad {$vs_local_path}: Verbinden von Objekt {$vn_object_id} und Medium {$vn_rep_id} erfolgreich.", Zend_Log::INFO);
				}
			}
		}

		unset($t_rep);
		unset($t_oxor);
		mmsGC();

		print CLIProgressBar::next();
	}

	print CLIProgressBar::finish();

	return true;
}
# ---------------------------------------------------------------------
