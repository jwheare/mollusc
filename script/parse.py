#!/usr/bin/env python
# -*- coding: UTF-8 -*-
import os, sys
from lxml import html
import csv

filelist = [e for e in os.listdir('html') if e != '.DS_Store']
first = filelist[0][:-5]
last = filelist[-1][:-5]

def parse_table(table):
    rows = history.cssselect("tr")
    # First row is the header
    header = rows.pop(0)
    headings = [heading.text for heading in header.cssselect("th")]
    data = []
    for row in rows:
        row_data = {}
        cells = row.cssselect("td")
        for i, h in enumerate(headings):
            row_data[headings[i]] = cells[i].text.strip().replace('\r', '').replace('\t', '').replace('\n', '')
        data.append(row_data)
    return headings, data

outfile = open("%s-%s.csv" % (first, last), 'wb')

for i, statement in enumerate(filelist):
    # Parse the journey table
    doc = html.parse('html/%s' % statement).getroot()
    history = doc.find_class('journeyhistory')[0]
    headings, data = parse_table(history)
    # Initialise DictWriter and write header is this is the first file
    if (i is 0):
        writer = csv.DictWriter(outfile, headings)
        # This is writer.writeheader() in Python 2.7
        writer.writerow(dict(zip(headings, headings)))
    # Write rows
    last_date = None
    for row in data:
        # Process date
        if not row['Date'] and last_date:
            row['Date'] = last_date
        last_date = row['Date']
        # Process money
        row['Fare'] = row['Fare'].replace('£', '').replace(' ', '')
        row['Balance'] = row['Balance'].replace('£', '').replace(' ', '')
        row['Price cap'] = row['Price cap'].replace('£', '').replace(' ', '')
        print row
        writer.writerow(row)
    
outfile.close()
