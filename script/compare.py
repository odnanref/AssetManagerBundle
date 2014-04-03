# -*- coding: iso-8859-15 -*-
import concurrent.futures
from multiprocessing import Process
import csv
import time
from datetime import datetime
import sqlite3
import codecs
import unicodedata
import sys

dbpath = 'patrimonial.db'

db = sqlite3.connect(dbpath, timeout=2)
c = db.cursor()
## enable utf8
c.execute('PRAGMA encoding = "UTF-8";');
db.commit()

sqlCREATE = 'CREATE TABLE IF NOT EXISTS item (_id INTEGER PRIMARY KEY AUTOINCREMENT, defid int, description text, value_unit real, datain int, dataviewed int, dataout int, ' + \
	'location_id text, qt real, state_id text, typeofItem_id text, aquiredtype text, protocol_id text, code text, protocol_code text, ' + \
	' ncm_reference text, depreciation real, supplier text, supplier_note_number text, ean128 text, searchable text);'

sqlCREATE_prot = 'CREATE TABLE IF NOT EXISTS protocol ( _id INTEGER PRIMARY KEY AUTOINCREMENT , description text, code text, tcount real);'
sqlCREATE_ncm = 'CREATE TABLE IF NOT EXISTS ncm_reference ( _id INTEGER PRIMARY KEY AUTOINCREMENT , description text, code text, depreciation real, tcount real);'
sqlCREATE_typeofitem = 'CREATE TABLE IF NOT EXISTS typeofitem ( _id INTEGER PRIMARY KEY AUTOINCREMENT , description text, code text, tcount real);'
sqlCREATE_state = 'CREATE TABLE IF NOT EXISTS state (_id INTEGER PRIMARY KEY AUTOINCREMENT , description text, code text, tcount real);'
sqlCREATE_location = 'CREATE TABLE IF NOT EXISTS location (_id INTEGER PRIMARY KEY AUTOINCREMENT , description text, code text, tcount real);'
sqlCREATE_last = 'CREATE TABLE IF NOT EXISTS last312012 (_id INTEGER PRIMARY KEY AUTOINCREMENT , description text, code text, qt real, state text, location text, value_unit real, aquiredtype text, typeofItem text, datain text, searchable text )'
sqlCREATE_AQUIREDTYPES = 'CREATE TABLE IF NOT EXISTS aquiredtype (_id INTEGER PRIMARY KEY AUTOINCREMENT, code text, description text, tcount integer);'

sqlCREATE_view_missingvalue = 'CREATE VIEW IF NOT EXISTS missingvalue AS SELECT * FROM item WHERE aquiredtype != "COMODATO" AND aquiredtype != "DOADO" AND (value_unit = "" OR value_unit = 0 ) AND protocol_id != "NPATRIMONIAR" '
sqlCREATE_VIEW_RESULT = 'CREATE VIEW IF NOT EXISTS RESULT AS SELECT item.code, item.description, date(item.datain, "unixepoch" ) as datain, item.value_unit as value_unit, item.qt as qt, state.description as state, location.description as location, protocol.description as protocol, typeofitem.description as TypeOfItem, ncmr.code as ncm_reference, ncmr.depreciation as depreciation, item.protocol_code as extcode, item.supplier as supplier, item.supplier_note_number as supplier_note_number, ean128 FROM item inner join location ON (item.location_id = location.code) inner join protocol ON (protocol.code = item.protocol_id ) INNER JOIN typeofItem ON (item.typeofItem_id = typeofItem.code) LEFT JOIN ncm_reference ncmr ON (ncmr.code = item.ncm_reference) INNER JOIN state ON (state.code = item.state_id) WHERE item.protocol_id != "NPATRIMONIAR" AND item.aquiredtype != "COMODATO" AND item.aquiredtype != "DOADO" AND protocol.description IN ("", "PAHI", "MAIS_SAUDE", "APARFILHO", "Ministerio do Tabalho e Previdencia Social FUNRURAL");'

sqlCREATE_VIEW_FORPRINT = "CREATE VIEW IF NOT EXISTS forprint as select i.code as code, i.description as description, i.ean128 as ean128, l.description as location, i.protocol_id as protocol, l.code as location_id, strftime('%Y-%m-%d', date(i.datain, 'unixepoch')) as datain from item i inner join location l on (l.code = i.location_id);"

sqlCREATE_counters = 'create table counters (_id int auto_increment primary key, name text, value text);'

sqlFillCounters1 = 'INSERT INTO counters (name, value) VALUES (\'code\', \'1\');'
sqlFillCounters2 = 'INSERT INTO counters (name, value) VALUES (\'defid\', \'1\');'

Execute = [sqlCREATE, sqlCREATE_prot, sqlCREATE_typeofitem, sqlCREATE_state, sqlCREATE_location, sqlCREATE_last, sqlCREATE_ncm, sqlCREATE_view_missingvalue, sqlCREATE_VIEW_RESULT, sqlCREATE_AQUIREDTYPES, sqlCREATE_VIEW_FORPRINT]

for qry in Execute:
#	print (qry)
	c.execute(qry)

db.commit()
db.close()

def searchLocationCode(db, location):
	if str(location).replace(' ', '') == '':
		location = ''
	
	tmpcode = unicodedata.normalize('NFKD', str(location).replace(' ', '').upper()).encode('ascii', 'ignore')

	c = db.cursor()
	c.execute('SELECT code FROM location WHERE code = ?', [tmpcode])
	code = c.fetchone()
	if (code != None):
		c.execute("UPDATE location set tcount=tcount+1 WHERE code = ?", [tmpcode])
		db.commit()
		return code[0]
	else:
		c.execute('INSERT INTO location (code, description, tcount) VALUES (?, ?, 1) ', [tmpcode, location])
		db.commit()
		c.execute('SELECT code FROM location WHERE ( code = ? )', [tmpcode])
		code = c.fetchone()
		return code[0]

def searchTypeOfItemCode(db, toi):
	if str(toi).replace(' ', '') == '':
		toi = ''
	
	tmpcode = unicodedata.normalize('NFKD', toi.replace(' ', '').upper()).encode('ascii', 'ignore')

	c = db.cursor()
	c.execute('SELECT code FROM typeofitem WHERE ( code = ? )', [tmpcode])
	code = c.fetchone()

	if (code != None):
		c.execute("UPDATE typeofitem set tcount=tcount+1 WHERE code = ?", [tmpcode])
		db.commit()
		return code[0]
	else:
		c.execute('INSERT INTO typeofitem (code, description, tcount) VALUES (?, ?, 1) ', [tmpcode, str(toi)])
		db.commit()
		c.execute('SELECT code FROM typeofitem WHERE ( code = ? )', [tmpcode])
		code = c.fetchone()
		return code[0]

def searchStateCode(db, state):
	if str(state).replace(' ', '') == '':
		state = ''

	tmpcode = unicodedata.normalize('NFKD', state.replace(' ', '').upper()).encode('ascii', 'ignore')

	c = db.cursor()
	c.execute('SELECT code FROM state WHERE description = ?', [state])
	code = c.fetchone()
	if (code != None):
		c.execute("UPDATE state set tcount=tcount+1 WHERE code = ?", [tmpcode])
		db.commit()
		return code[0]

	else:
		c.execute('INSERT INTO state (code, description, tcount) VALUES (?, ?, 1) ', [tmpcode, state])
		db.commit()
		c.execute('SELECT code FROM state WHERE ( code = ? )', [tmpcode])
		return c.fetchone()[0]

def updateReference(db, ncm):
	if (str(ncm).replace(' ', '') == ''):
		return ''

	c = db.cursor()
	c.execute('SELECT code FROM ncm_reference WHERE code = ?', [ncm])
	db.commit()
	code = c.fetchone()
	if (code != None):
		c.execute('UPDATE ncm_reference set tcount=tcount+1 WHERE code = ?', [ncm])
		db.commit()
		return code[0]
	c.execute('INSERT INTO ncm_reference (code, tcount) VALUES (?, 1)', [ncm])
	db.commit()
	return ncm

def insertReference(ncm):
	db = sqlite3.connect(dbpath, timeout=2)
	if (str(ncm['ncm']).replace(' ', '') == ''):
		db.close()
		return ''

	ncm['ncm'] = ncm['ncm'].replace(' ', '')

	if len(ncm['ncm']) <= 5: # theoretically there is nothing lower then 4
		ncm['ncm'] = str(ncm['ncm']).replace('.', '')

	c = db.cursor()
	c.execute('SELECT code from ncm_reference WHERE code = ?', [ncm['ncm']])
	res = c.fetchone()
	if res == None:
		c.execute('INSERT INTO ncm_reference (code, description, depreciation, tcount) VALUES (?, ?, ?, 0)', \
				[str(ncm['ncm']), str(ncm['description']), str(ncm['depreciation'])])
		db.commit()
		sys.stdout.write('.')
	db.close()
	return ncm


def itemExists(description, code):
	db = sqlite3.connect(dbpath, timeout=2)
	if (str(description).replace(' ', '') == ''):
		return ''
	if (str(code).replace(' ', '') == ''):
		return ''
	
	c = db.cursor()
	c.execute('SELECT * FROM item WHERE defid = ? AND description = ?', [code, description])
	item = c.fetchone()
	db.close()
	if (item != None):
		return item[0]
	return None


def searchCode(db, code):
	if str(code).replace(' ', '') == '':
		code = ''
	c = db.cursor()
	c.execute('SELECT * FROM item WHERE code = ?', [code])
	item = c.fetchone()
	return item

def searchProtocolCode(db, protocol):
	if str(protocol).replace(' ', '') == '' or protocol == None:
		protocol = ''
	c = db.cursor()
	c.execute('SELECT code FROM protocol WHERE code = ?', [protocol.replace(' ', '').upper()])
	code = c.fetchone()
	if (code != None):
		c.execute('UPDATE protocol set tcount=tcount+1 WHERE code = ?', [protocol.replace(' ', '').upper()])
		db.commit()
		return code[0]

	else:
		c.execute('INSERT INTO protocol (code, description, tcount) VALUES (?, ?, 1) ', [protocol.replace(' ', '').upper(), protocol])
		db.commit()
		c.execute('SELECT code FROM protocol WHERE ( code = ? )', [ protocol.replace(' ', '').upper()])
		return c.fetchone()[0]

def searchAquiredtype(db, protocol):
	if str(protocol).replace(' ', '') == '' or protocol == None:
		protocol = ''
	c = db.cursor()
	c.execute('SELECT code FROM aquiredtype WHERE code = ?', [protocol.replace(' ', '').upper()])
	code = c.fetchone()
	if (code != None):
		c.execute('UPDATE aquiredtype set tcount=tcount+1 WHERE code = ?', [protocol.replace(' ', '').upper()])
		db.commit()
		return code[0]

	else:
		c.execute('INSERT INTO aquiredtype (code, description, tcount) VALUES (?, ?, 1) ', [protocol.replace(' ', '').upper(), protocol])
		db.commit()
		c.execute('SELECT code FROM aquiredtype WHERE ( code = ? )', [ protocol.replace(' ', '').upper()])
		return c.fetchone()[0]

def updateItem(db, item):
	if (item == None):
		db.close()
		raise Exception('No item to update.')

	item['location_id'] = searchLocationCode(db, item['location_id'])
	item['typeofItem_id'] = searchTypeOfItemCode(db, item['typeofItem_id'])
	item['state_id'] = searchStateCode(db, item['state_id'])
	item['protocol_id'] = searchProtocolCode(db, item['protocol_id'])
	item['aquiredtype'] = searchAquiredtype(db, item['aquiredtype'])
	if (item['qt'].replace(' ', '') == ''):
		item['qt'] = 1
	if (str(item['ncm_reference']).replace(' ', '') != ''):
		updateReference(db, item['ncm_reference'])

	item['searchable'] = unicodedata.normalize('NFKD', item['description']).encode('ascii', 'ignore')
	c = db.cursor()

	c.execute('UPDATE item set description = ? , ' + \
		'value_unit = ? , datain = ? , location_id = ? , qt = ? , state_id = ? , typeofItem_id = ? , aquiredtype = ? , ' + \
		'protocol_id = ? , code = ? , protocol_code = ? , ncm_reference = ? , depreciation = ? , supplier = ? , ' + \
		'supplier_note_number = ? , ean128 = ? , searchable = ? WHERE defid = ? ' , \
		(item['description'], item['value_unit'], item['datain'], \
                item ['location_id'], item['qt'], item['state_id'], item['typeofItem_id'], item['aquiredtype'],\
		item['protocol_id'], \
		item['code'], item['protocol_code'], item['ncm_reference'], item['depreciation'], \
		item['supplier'], item['supplier_note_number'], item['ean128'], item['searchable'], item['defid'])
		)
	db.commit()

	db.close()
	return itemExists(item['description'], item['defid'])

def insertItem(item):
	db = sqlite3.connect(dbpath, timeout=1)
	exists = itemExists(item['description'], item['defid'])
	if (None != exists):
		return updateItem(db, item)

	item['location_id'] = searchLocationCode(db, item['location_id'])
	item['typeofItem_id'] = searchTypeOfItemCode(db, item['typeofItem_id'])
	item['state_id'] = searchStateCode(db, item['state_id'])
	item['protocol_id'] = searchProtocolCode(db, item['protocol_id'])
	item['aquiredtype'] = searchAquiredtype(db, item['aquiredtype'])
	if (item['qt'].replace(' ', '') == ''):
		item['qt'] = 1
	if (str(item['ncm_reference']).replace(' ', '') != ''):
		updateReference(db, item['ncm_reference'])

	item['searchable'] = unicodedata.normalize('NFKD', item['description']).encode('ascii', 'ignore')
	c = db.cursor()
	c.execute('INSERT INTO item (description, value_unit, datain, location_id, qt, state_id, typeofItem_id ' + \
			', aquiredtype, protocol_id, code, protocol_code, ncm_reference, depreciation, supplier, supplier_note_number, ean128, searchable, defid) VALUES ' + \
			'(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)', [item['description'], item['value_unit'], item['datain'], \
                        item ['location_id'], item['qt'], item['state_id'], item['typeofItem_id'], item['aquiredtype'], item['protocol_id'], \
                                                        item['code'], item['protocol_code'], item['ncm_reference'], item['depreciation'], \
							item['supplier'], item['supplier_note_number'], item['ean128'], item['searchable'], item['defid']])
	db.commit()
	db.close()
	return True

def insertLast(item):
	db = sqlite3.connect(dbpath, timeout=2)
#	last312012 (_id int primary key, description text, code text, qt real, state text, location text, value_unit real, aquiredtype text, typeofItem text )'
	# ['AssetType', 'Description', 'Id', 'location','aquisitiontype','datain','Qtd','value_unit']
	value = {}
	value['location'] = item['location']
	value['code'] = item['Id']
	value['description'] = item['Description']
	value['qt'] = item['Qtd']
	value['value_unit'] = item['value_unit']
	value['aquiredtype'] = item['aquisitiontype']
	value['typeofItem'] = item['AssetType']
	value['datain'] = item['datain']
	value['searchable'] = unicodedata.normalize('NFKD', item['Description']).encode('ascii', 'ignore')
	res = db.cursor().execute('select * from last312012 where code = ? ', [value['code']]).fetchone()
	if (res != None):
		db.close()
		return True

	c = db.cursor()
	c.execute('INSERT INTO last312012 (description, code, qt, location, value_unit, aquiredtype, typeofItem, datain,searchable) VALUES ' + \
			'(?, ?, ?, ?, ?, ?, ?, ?, ?)', [value['description'], value['code'], value['qt'], \
                        value['location'], value['value_unit'], value['aquiredtype'], value['typeofItem'], value['datain'], value['searchable']])
	db.commit()
	db.close()
	return True

rowtitle1 = ['AssetType', 'Description', 'Id', 'location','aquisitiontype','datain','Qtd','value_unit', 'depreciation']
rowtitle2 = ['ncm_reference','depreciation','defid','Quantidade','Description','Id','ORIGEM','value_unit', 'AssetType', 'state', 'location', 'datain', 'sourcebuy', 'extcode', 'supplier', 'supplier_note_number', 'ean128']

Out = {} # out to csv file
Old = {} # old list
New = {} # new list

OldLocations = [] # old named locations
NewLocations = [] # new named locations 
Missing = [] # missing items in new listing
CostDiff = {} # cost difference big or less then 10
Duplicate = [] # duplicate items found
ExtCode = [] # list of codes that dont have a protocol associated

def location_add(location, View):
        for i in View:
                if i == location:
                        return 1

        View.append(location)

def ncmToDb():
	with open('ListaACTUALNCM.csv', 'r') as ncmfile:
		print("reading ncms to database...")
		ncmList = csv.DictReader(ncmfile, ['ncm', 'description', 'depreciation'], dialect='excel', delimiter=';')
		for ncm in ncmList:
	#			print ('inserting ' + ncm['ncm'])
			insertReference(ncm)
#for miss in Missing:
#	print("missing " + miss)

def placeCost():
	for i in New:
		if (i in Old.keys()):
			temp = Old[i]

			if (temp['value_unit'].replace(' ','') == ''):
				temp['value_unit'] = 0
			if (New[i]['value_unit'].replace(' ','') == ''):
				New[i]['value_unit'] = 0

			New[i]['value_unit'] = str(New[i]['value_unit']).replace(',','.')
			temp['value_unit'] = str(temp['value_unit']).replace(',','.')

			if (temp['value_unit'] != New[i]['value_unit'] and str(New[i]['value_unit']) != "0"):
				print("value mismatch...\n" + New[i]['Description'] + " " + \
						str(i) + " \nvalue=" + str(New[i]['value_unit']) + \
						" old value=" + str(Old[i]['value_unit']))

				if (float(temp['value_unit']) > (float(New[i]['value_unit'])+10) or \
						(float(temp['value_unit']) < (float(New[i]['value_unit'])-10)) and \
						New[i]['value_unit'] != 0 ):
					print (";;;;Error determining cost;;;;")
					differ = { 'old':str(temp['value_unit']), 'new':str(New[i]['value_unit'])}
					CostDiff[i] = differ

def getValue(New, n):
	Value = {}
	Value['description'] = New[n]['Description']
	Value['state_id'] = New[n]['state']
	Value['location_id'] = New[n]['location']
	Value['typeofItem_id'] = New[n]['AssetType']
	if str(New[n]['Quantidade']).replace(' ', '') == "":
		if (n in Old.keys()):
			Value['qt'] = Old[n]['Qtd']
		else:
			Value['qt'] = "1"
	else:
		Value['qt'] = New[n]['Quantidade']
	
	if ((str(New[n]['value_unit']).replace(' ', '') == "0" or str(New[n]['value_unit']).replace(' ', '') == '') and (n in Old.keys() and str(Old[n]['value_unit']).replace(' ', '') != '' )):
		Value['value_unit'] = str(Old[n]['value_unit'])
	elif ( str(New[n]['extcode']).replace(' ', '') != '' and (New[n]['extcode'] in Old.keys()) ):
		extcode = New[n]['extcode']
		Value['value_unit'] = str(Old[extcode]['value_unit'])
	else:
		if str(New[n]['value_unit']).replace(' ', '') == '0'or str(New[n]['value_unit']).replace(' ', '') == '':
			Value['value_unit'] = '0'
		else:
			Value['value_unit'] = str(New[n]['value_unit'])
	Value['aquiredtype'] = New[n]['ORIGEM']
	Value['protocol_id'] = New[n]['sourcebuy']
	Value['code'] = New[n]['Id']
	Value['protocol_code'] = New[n]['extcode']
	Value['depreciation'] = New[n]['depreciation']
	Value['ncm_reference'] = New[n]['ncm_reference']
	Value['ean128'] = New[n]['ean128']
	
	if (n in Old.keys()):
		if( str(Old[n]['datain']).replace(' ', '') != ''):
			dz = datetime.strptime(Old[n]['datain'], '%d/%m/%Y')
			Value['datain'] = time.mktime(dz.timetuple())
		else:
			Value['datain'] = ''
	elif ( str(New[n]['extcode']).replace(' ', '') != '' and (New[n]['extcode'] in Old.keys()) ):
		extcode = New[n]['extcode']
		dz = datetime.strptime(str(Old[extcode]['datain']), '%d/%m/%Y')
		Value['datain'] = time.mktime(dz.timetuple())
	elif (str(New[n]['datain']).replace(' ', '') != '' ):
		dz = datetime.strptime(New[n]['datain'], '%d/%m/%Y')
		Value['datain'] = time.mktime(dz.timetuple())
	else:
		Value['datain'] = ''
	Value['supplier'] = New[n]['supplier']
	Value['supplier_note_number'] = New[n]['supplier_note_number']
	Value['defid'] = New[n]['defid']
	
	return Value


def toDB(New):
	for n in New:
		insertItem(getValue(New, n))
		sys.stdout.write('.')

def writeCSVMissing():
	with open('Missing.csv', 'w', newline='') as csvfile:
	    writer = csv.writer(csvfile, delimiter=';',
	                            quotechar='"', quoting=csv.QUOTE_MINIMAL)
	    for i in Missing:
		    writer.writerow(i.split(';'))
	    for i in Duplicate:
		    writer.writerow(i.split(';'))

def writeCSVResult():
	db = sqlite3.connect(dbpath)
	with open('Result.csv', 'w', newline='\n') as csvout:
		writer = csv.writer(csvout, delimiter=';', quotechar="'", quoting=csv.QUOTE_MINIMAL)
		sql_wrt = 'SELECT ncm_reference, depreciation, qt, description, code, location, value_unit, TypeOfItem, state, datain, protocol, extcode, supplier_note_number, supplier FROM RESULT'
		q = db.cursor()
		for row in q.execute(sql_wrt):
			writer.writerow(row)
	db.close()

def readOld():
	x = 0
	z = 0
	# counter for missing code or code ND
	cND=0
	with open('antigo-report.csv', 'r') as csvfile:
		print("reading new...")
		newcsv = open('CONTROLO_DE_PATRIMONIO_2.csv', 'r')
		itemsFileNew = csv.DictReader(newcsv, rowtitle2, dialect='excel', delimiter=';')
		for row in itemsFileNew:
			if (z>0 and row['Id'].replace(' ', '') != ''):
				location_add(row['location'], NewLocations)
				if str(row['extcode']).replace(' ', '') != '' and str(row['sourcebuy']).replace(' ', '')  == '': # if theres no protocol add it
					ExtCode.append(row['extcode']) # add extcode to list
				k = row['Id']
				if str(k).replace(' ', '') == "ND" or str(k).replace(' ', '') == "MS" or str(k).replace(' ', '') == "99999":
					cND=(cND+1)
					k=cND
				elif (row['Id'] in New.keys() and str(row['Id']).replace(' ', '') != 'ND' and str(row['Id']).replace(' ', '') != '99999' and str(row['Id']).replace(' ', '') != 'MS'):
					Duplicate.append( 'DUPLICATE;' + row['Description'] + ';' + str(row['Id']) + ';' + row['value_unit'] + ';' + row['location'] )
					Duplicate.append( 'Duplicate;' + str(New[row['Id']]['Description']) + ';' + str(row['Id']) + ';' + str(New[row['Id']]['value_unit']) + ';' + str(New[row['Id']]['location']) )
				else:
					k = row['Id']
				New[k] = row
			z += 1

		print("reading old...")
		itemsFileOld = csv.DictReader(csvfile, rowtitle1,dialect='excel', delimiter=';')
		for row in itemsFileOld:
			if (x > 0 and row['Id'].replace(' ', '') != ''):
				Old[row['Id']] = row
				location_add(row['location'], OldLocations)
				insertLast(row)
				if (row['Id'] not in New.keys() and row['Id'] not in ExtCode):
						Missing.append( row['Id'] + ';' + row['Description'] + ';' + row['location'] + ';' + row['value_unit'] )

			x+=1
	placeCost()

	for c in CostDiff:
		print(c + "| old=" + CostDiff[c]['old'] + " new=" + CostDiff[c]['new'])

	for i in Duplicate:
		print(i)



if __name__ == '__main__':
	readOld()

	ncmToDb()
	toDB(New)
	print ('csv result and missing ... ')
	writeCSVMissing()
	writeCSVResult()

	print ("done out...")
	
	print (">> total missing: " + str(len(Missing)) )
	print (">> total duplicates: " + str(len(Duplicate)) )
	print (">> total of " + str(len(CostDiff)) + " mismatches")
	
	time.sleep(10000)

# 
# select (case when qt=1 then count() else count()+sum(qt) end) as qt, code, description, typeofItem_id from missingvalue group by description;
#  select count() from last312012 l left join item i on (i.code = l.code or i.protocol_code = l.code) where i.code is null;

