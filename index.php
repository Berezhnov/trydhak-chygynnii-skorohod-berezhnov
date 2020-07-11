<?php

require 'lib/vendor/autoload.php';
require 'lib/Morf.php';
require 'lib/Sinonims.php';
use Medoo\Medoo;

ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '1000');
ini_set('xdebug.var_display_max_data', '1024');


header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] . "");
header("Access-Control-Allow-Headers: content-type, content-length");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, PUT, DELETE, OPTIONS');

ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_startup_errors', 1);

function getDb ()
{
	return new Medoo(['database_type' => 'mysql', 'database_name' => 'tryd', 'server' => 'localhost', 'username' => 'root', 'password' => 'e181J*h2']);
}

function sendResponse ($data, $errorMessage = '')
{
	$success = true;
	if ($errorMessage !== '')
	{
		$success = false;
	}

	$response = ['success' => $success, 'message' => $errorMessage, 'data' => $data];
	echo json_encode($response);
	die;
}

function preprocessRequest ($checkAuth = true)
{
	$request = Flight::request();
	if ($request -> method === 'OPTIONS')
	{
		die;
	}

	$somethingWrong = false;
	if ($somethingWrong)
	{
		sendResponse([], 'Error');
	}

	if ($request -> method === 'POST' or $request -> method === 'PUT')
	{
		return json_decode($request -> getBody(), true);
	}
	else
	{
		return null;
	}
}

// ФИКСИРОВАНИЕ СОФТ СКИЛЛОВ В БАЗУ
Flight::route('GET /softSkills', function(){

	$sinsController = new Sinonims();
	$db = getDb();

	$skills = [
		'коммуникабельность' => [[['words' => ['друг', 'дружеский', 'команда', 'консультация']]]],
		'управленческие качества' => [[['words' => ['управление', 'руководство', 'лидерство']]]],
		'креативность' => [[['words' => ['креативность', 'придумывать']]]],
		'стрессоустойчивость' => [[['words' => ['стрессоустойчивость', 'адаптивность']]]],
		'опыт в продажах' => [[['words' => ['продажа', 'продажи', 'продвижение'], 'part' => ['существительное', 'глагол']], ['part' => ['существительное']]]],
	];


	// СИНОНИМИЗАЦИЯ И ЗАПИСЬ В БАЗУ
	foreach ($skills as $skillName => &$rules)
	{
		foreach ($rules as &$rule)
		{
			foreach ($rule as &$ruleItem)
			{
				if (!isset($ruleItem['words']))
				{
					continue;
				}

				$allSins = [];
				$notExactBaseWords = array_filter($ruleItem['words'], function ($word) {return mb_substr($word, 0, 1, 'utf-8') !== '!' ? true : false;});
				$sins = array_values($sinsController -> parseSinonims($notExactBaseWords));
				foreach ($sins as $wordSins)
				{
					$allSins = array_merge($allSins, $wordSins);
				}

				$allSins = array_merge($allSins, $ruleItem['words']);
				$allSins[] = $skillName;
				$allSins = array_values(array_unique($allSins));
				$ruleItem['words'] = $allSins;
			}
		}

		$existingSkill = $db -> query("SELECT * FROM softSkills WHERE name = '$skillName'") -> fetch();
		if ($existingSkill !== false)
		{
			$db -> update('softSkills', ['rules' => json_encode($rules)], ['id' => $existingSkill['id']]);
		}
		else
		{
			$db -> insert('softSkills', ['name' => $skillName, 'rules' => json_encode($rules)]);
		}
	}
});

Flight::route('GET /hardSkills', function(){

	$sinsController = new Sinonims();
	$db = getDb();

	$skills = [
		'обслуживание посетителей' => ['!обслуживание посетителей'],
		'продажа лекарственных препаратов' => ['!отпуск лс', '!продажа лс', '!отпуск лекарственных средств'],
		'проведение презентаций' => ['!проведение презентаций', 'Power Point', '!Power Point', '!microsoft pp'],
		'знания об антибиотиках' => ['!антибиотки'],
		'работа с документацией' => ['!документация', '!отчет'],
		'продвижение, реклама' => ['!реклама'],
		'программы - Microsoft Word' => ['!word', '!msword', '!ворд'],
		'программы - Microsoft Excel' => ['эксель', 'Excel', 'excel'],
		'анализ качества' => ['!экспертиза'],
		'медицина - колопроктология' => ['!колопроктолог', '!колопроктология', '!колопроктологический'],
		'реабилитация пациентов' => ['!реабилитация'],
		'медицина - фармацевтика' => ['!фармацевт', '!фармацевтический'],
		'медицина - проктология' => ['!геморрой', '!анальный', '!проктолог', '!проктология'],
		'медицина - токсикология' => ['!токсиколог', '!токсикология'],
		'медицина - инъекции' => ['!инъекция', '!инъекций', '!инъекционный'],
		'медицина - капельницы' => ['капельниц'],
		'медицина - малоинвазивного лечение' => ['!малоинвазивный'],
		'медицина - вирусология' => ['!инфекционный', '!инфекция', '!ВИЧ'],
		'программы - 1С Склад' => ['!1С Склад'],
		'медицина - стоматология' => ['!врачу-стоматологу', '!стоматология', '!стоматологический', '!кариеса', '!налет'],
		'медицина - эндоскопия' => ['!эндоскопист'],
		'медицина - онкология' => ['!новообразование'],
		'медицина - диспансеризация' => ['!диспансер', '!диспансеризация'],
		'медицина - неврология' => ['!невролог', '!неврология', '!неврологии', '!неврологический'],
		'медицина - кардиология' => ['кардиология'],
		'медицина - пульмонология' => ['!пульмонология'],
		'медицина - хирургия' => ['!хирургия', '!хирургический'],
		'медицина - терапевт' => ['!терапевт', '!терапевтический'],
		'медицина - гинекология' => ['!гинеколог', '!гинекологический', '!гинекология'],
	];

	foreach ($skills as $skillName => &$baseWords)
	{
		$allSins = [];
		$notExactBaseWords = array_filter($baseWords, function ($word) {return mb_substr($word, 0, 1, 'utf-8') !== '!' ? true : false;});

		$sins = array_values($sinsController -> parseSinonims($notExactBaseWords));
		foreach ($sins as $wordSins)
		{
			$allSins = array_merge($allSins, $wordSins);
		}

		$allSins = array_merge($allSins, $baseWords);
		$allSins[] = $skillName;
		$allSins = array_values(array_unique($allSins));

		$existingSkill = $db -> query("SELECT * FROM hardSkills WHERE name = '$skillName'") -> fetch();
		if ($existingSkill !== false)
		{
			$db -> update('hardSkills', ['sins' => json_encode($allSins)], ['id' => $existingSkill['id']]);
		}
		else
		{
			$db -> insert('hardSkills', ['name' => $skillName, 'sins' => json_encode($allSins)]);
		}
	}
});

Flight::route('GET /test2', function(){

	$db = getDb();
	$file = fopen('./data/vacs_train.csv', 'rb');
	$vacs = [];

	for ($i = 0; $i < 100000; $i++) {
		$line = fgets($file);
		$columns = array_slice(explode(';', $line), 1);

		if ($i === 0)
		{
			continue;
		}

		for ($j = 0, $max2 = sizeof($columns); $j < $max2; $j++)
		{
			if ($j === 21)
			{
				$j++;
				while (isset($columns[$j + 1]) and $columns[$j + 1] !== 'train' and $columns[$j + 1] !== 'test')
				{
					$columns[21] .= ' split_req ' . $columns[$j];
					unset($columns[$j]);
					$j++;
				}
			}
		}
		$columns = array_values($columns);

		if ($line !== false) {
			$vac = array_combine(['id', 'name', 'name.lemm', 'area.name', 'city', 'company.id', 'company', 'company_link',
				'publication_date', 'salary_from', 'salary_currency', 'employment', 'employment.name', 'schedule', 'schedule.name',
				'experience', 'experience.name', 'key_skills', 'specializations', 'specializations.names', 'description', 'description.lemm', 'type'], $columns);

			if (!preg_match("/(^|[. ,-])фарм|(^|[. ,-])апте|(^|[. ,-])медиц|(^|[. ,-])врач|(^|[. ,-])лекар/i", $vac['specializations.names']))
			{
				continue;
			}

			$vacs[] = $vac;
		}


	}

	$morf = new Morf();
	for ($i = 0, $max = sizeof($vacs); $i < $max; $i++)
	{
		echo "SKILLS:{$vacs[$i]['key_skills']}<br>SPECIALIZATION: {$vacs[$i]['specializations.names']}<br>DESCRIPTION:{$vacs[$i]['description.lemm']}<br><br>";
	}
});

Flight::route('GET /resumes', function(){

	$morf = new Morf();
	$db = getDb();
	$file = fopen('./data/resume_train.csv', 'rb');
	$resumes = [];

	$rawSoftSkills = $db -> select('softSkills', '*');
	$softSkills = [];
	foreach ($rawSoftSkills as &$skillInfo)
	{
		$rules = json_decode($skillInfo['rules'], true);
		$formattedSoftSkill = [];

		foreach ($rules as &$rule)
		{
			foreach ($rule as &$ruleItem)
			{
				if (!isset($ruleItem['words']))
				{
					continue;
				}
				$sins = $ruleItem['words'];
				array_walk($sins, function(&$word) {return preg_replace("/^!/", '', $word);});
				$normSins = $morf -> morfPhrases2($sins);

				array_walk($normSins, function (&$sin) {$sin = $sin[0]; });
				// получаем все лексемы
				//$lexemes = $morf -> morfPhrases($sins);


				// убираем лишние синонимы и квазисинонимы
				if ($skillInfo['name'] === 'опыт в продажах')
				{
					$normSins = array_filter($normSins, function ($sin) {return !in_array($sin['lemm'], ['консультирование', 'прием', 'выполнение', 'мероприятие', 'осуществление', 'трещина', 'работа', 'акт', 'формирование', 'обследование', 'деятельность']);});
				}

				if ($skillInfo['name'] === 'коммуникабельность')
				{
					$normSins = array_filter($normSins, function ($sin) {return !in_array($sin['lemm'], ['экспертиза', 'работа', 'в четыре руки', 'он', 'руководитель', 'кабинет', 'склад', 'формирование', 'врач', 'хирург']);});
				}

				if ($skillInfo['name'] === 'управленческие качества')
				{
					$normSins = array_filter($normSins, function ($sin) {return !in_array($sin['lemm'], ['помощь', 'прием', 'метод', 'вести', 'ремонт']);});
				}

				if ($skillInfo['name'] === 'креативность')
				{
					$normSins = array_filter($normSins, function ($sin) {return !in_array($sin['lemm'], ['находиться']);});
				}

				$ruleItem['words'] = $normSins;
			}

			$formattedSoftSkill[] = $rule;
		}

		$softSkills[] = ['name' => $skillInfo['name'], 'rules' => $formattedSoftSkill];
	}

	$rawHardSkills = $db -> select('hardSkills', '*');
	$hardSkills = [];
	foreach ($rawHardSkills as $skill)
	{
		$sins = json_decode($skill['sins'], true);
		array_walk($sins, function(&$word) {return preg_replace("/^!/", '', $word);});
		$normSins = array_values(array_unique(array_merge($sins, $morf -> morfPhrases($sins))));

		if ($skill['name'] === 'опыт в продажах')
		{
			$normSins = array_values(array_diff($normSins, []));
		}

		$hardSkills[] = ['name' => $skill['name'], 'normSins' => $normSins];
	}

	for ($i = 0; $i < 10000; $i++) {
		$line = fgets($file);
		try
		{

		if ($i === 0)
		{
			continue;
		}

		$columns = preg_split("/;(?=[^  ])/m", $line);

		if ($i === 0)
		{
			continue;
		}

		for ($j = 0, $max2 = sizeof($columns); $j < $max2; $j++)
		{
			$columns[$j] = preg_replace("/\r|\n/", '', $columns[$j]);
			if ($j === 3)
			{

				$j++;
				while (!preg_match("/^\d{4}-\d{2}$/", $columns[$j]))
				{
					$columns[3] .= ' split_req ' . $columns[$j];

					unset($columns[$j]);
					$j++;
				}
			}
		}
		$columns = array_values($columns);

			if ($line !== false) {
				unset($columns[0]);
				unset($columns[6]);
				$resumes[] = array_combine([/*'uuid', */'position', 'organization', 'description', 'start', 'end'/*, 'type'*/], array_values($columns));
			}
		}
		catch (Exception $e)
		{
			continue;
		}

	}
	fclose($file);

	$positions = [];
	for ($i = 0, $max = sizeof($resumes); $i < $max; $i++)
	{
		$positions[] = $resumes[$i]['position'];
	}
	$normPositions = $morf -> morfPhrases2($positions);
	for ($i = 0, $max = sizeof($resumes); $i < $max; $i++)
	{
		$resumes[$i]['normPosition'] = implode(' ', array_map(function ($wordInfo) {return $wordInfo['lemm'];}, $normPositions[$i]));
		$resumes[$i]['normPositionParts'] = implode(' ', array_map(function ($wordInfo) {return $wordInfo['part'];}, $normPositions[$i]));
	}
	unset($positions);
	unset($normPositions);

	$descriptions = [];
	for ($i = 0, $max = sizeof($resumes); $i < $max; $i++)
	{
		$descriptions[] = $resumes[$i]['description'];
	}
	$normDescriptions = $morf -> morfPhrases2($descriptions);
	for ($i = 0, $max = sizeof($resumes); $i < $max; $i++)
	{
		foreach ($normDescriptions[$i] as $k => $item)
		{
			if (!isset($item['part']))
			{
				unset($normDescriptions[$i][$k]);
			}
		}

		$resumes[$i]['normDescription'] = implode(' ', array_map(function ($wordInfo) {return $wordInfo['lemm'];}, $normDescriptions[$i]));
		$resumes[$i]['normDescriptionParts'] = implode(' ', array_map(function ($wordInfo) {return $wordInfo['part'];}, $normDescriptions[$i]));
	}
	unset($descriptions);
	unset($normDescriptions);

	for ($i = 0, $max = sizeof($resumes); $i < $max; $i++)
	{
		if (!preg_match("/фарм|апте|мед|врач|лекар/i", $resumes[$i]['normPosition'] . ' ' . $resumes[$i]['organization']) or !isset($resumes[$i]['organization']) or $resumes[$i]['organization'] === '')
		{
			continue;
		}

		/*if (preg_match("/лет|год|c |по /", $resumes[$i]['normDescription']))
		{
			echo $resumes[$i]['normDescription'] . '<br><br>';
		}*/

		echo '<h3 style="margin-bottom: 0">' . $resumes[$i]['organization'] . ' / '. $resumes[$i]['position'] .'</h3>';
		echo $resumes[$i]['description'] . '<br>';

		$text = $resumes[$i]['normDescription'] . ' ' . $resumes[$i]['normPosition'];
		$textWords = explode(' ', $text);

		$textParts = $resumes[$i]['normDescriptionParts'] . ' ' . $resumes[$i]['normPositionParts'];
		$textPartsWords = explode(' ', $textParts);

		$resumes[$i]['softSkills'] = [];
		foreach ($softSkills as $skill)
		{
			foreach ($skill['rules'] as $rule)
			{
				$ruleISOK = true;
				$ruleProcessInfo = [];
				$ruleItemWords = array_values(array_map(function ($wordInfo) {return $wordInfo['lemm'];}, $rule[0]['words']));

				for ($j = 0, $max2 = sizeof($ruleItemWords); $j < $max2; $j++)
				{
					$ruleProcessInfoString = "";
					// ищем вхождения слова в текст
					$starts = array_keys ($textWords, $ruleItemWords[$j]);
					for ($k = 0, $max3 = sizeof($starts); $k < $max3; $k++)
					{
						$o = 0;
						// идем по элементам правила, чтобы удостовериться, что они все выполняются по словам и частям речи
						foreach ($rule as $rulePart)
						{
							if (!isset($textWords[$starts[$k] + $o]))
							{
								$ruleISOK = false;
								break;
							}
							$textWord = $textWords[$starts[$k] + $o];
							$textWordPart = $textPartsWords[$starts[$k] + $o];

							$ruleProcessInfoString .= "$textWord|$textWordPart, ";
							$ruleItemWordsTemp = isset($rulePart['words']) ? array_map(function ($wordInfo) {return $wordInfo['lemm'];}, $rulePart['words']) : [];

							if (isset($rulePart['part']) and $textWordPart !== '*' and !in_array($textWordPart, $rulePart['part']))
							{
								$ruleISOK = false;
								break;
							}

							if (sizeof($ruleItemWordsTemp) !== 0 and !in_array($textWord, $ruleItemWordsTemp))
							{
								$ruleISOK = false;
								break;
							}

							$o++;
						}

						if ($ruleISOK and ($ruleProcessInfoString !== ""))
						{
							$ruleProcessInfo[] = $ruleProcessInfoString;
						}
					}
				}

				if ($ruleISOK and !isset($resumes[$i]['softSkills'][$skill['name']]) and sizeof($ruleProcessInfo) !== 0)
				{
					$resumes[$i]['softSkills'][$skill['name']][] = implode(" ", $ruleProcessInfo);
				}
			}

/*			foreach ($skill['normSins'] as $normalizedSkillSinonim)
			{
				$formattedNormalizedSkillSinonim = preg_quote($normalizedSkillSinonim);

				if (preg_match("/(^|[. ,-])$formattedNormalizedSkillSinonim($|[. ,:-])/", $resumes[$i]['normDescription'] . ' ' . $resumes[$i]['normPosition']))
				{
					if (!isset($resumes[$i]['softSkills'][$skill['name']]))
					{
						$resumes[$i]['softSkills'][$skill['name']] = [];
					}
					$resumes[$i]['softSkills'][$skill['name']][] = $normalizedSkillSinonim;
				}
			}*/
		}

		$resumes[$i]['hardSkills'] = [];
		foreach ($hardSkills as $skill)
		{
			foreach ($skill['normSins'] as $normalizedSkillSinonim)
			{
				$formattedNormalizedSkillSinonim = preg_quote($normalizedSkillSinonim);

				if (preg_match("/(^|[. ,-])$formattedNormalizedSkillSinonim($|[. ,:-])/", $resumes[$i]['normDescription'] . ' ' . $resumes[$i]['normPosition']))
				{
					if (!isset($resumes[$i]['hardSkills'][$skill['name']]))
					{
						$resumes[$i]['hardSkills'][$skill['name']] = [];
					}
					$resumes[$i]['hardSkills'][$skill['name']][] = $normalizedSkillSinonim;
				}
			}
		}

		if (sizeof($resumes[$i]['softSkills']) !== 0)
		{
			$skillInfoAr = [];
			foreach ($resumes[$i]['softSkills'] as $skillName => $skillProcessInfoStrings)
			{
				//$skillInfoAr[] = "\t{$skillName} => " . implode(' | ', $skillProcessInfoStrings);
				$skillInfoAr[] = $skillName;
			}
			echo '<br>SOFT SKILLS:<br>-' . implode('<br>-', $skillInfoAr) . '<br>';
		}
		else
		{
			echo '<br>';
		}

		if (sizeof($resumes[$i]['hardSkills']) !== 0)
		{
			$skillInfoAr = [];
			foreach ($resumes[$i]['hardSkills'] as $skillName => $skillSinonims)
			{
				$skillInfoAr[] = "\t{$skillName} => " . implode(' | ', $skillSinonims);
			}
			echo '<br>HARD SKILLS:<br>-' . implode('<br>-', $skillInfoAr) . '<br><br>';
		}
		else
		{
			echo '<br>';
		}
	}
});

Flight::start();
