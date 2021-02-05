import json
import re
import sqlite3
import string


def addWord(wordDf, rStr):
    print(f"input: '{rStr}'")
    wEnd = rStr.find(' (')
    wd = rStr[:wEnd]
    altern = []
    if wd.find('/') > 0:
        mwd = wd.split('/')
        wd = mwd[0].strip()
        altern = mwd[1:]
    elif wd.find('(') >= 0:
        altern = [re.sub(r'[\(\)]', '', wd)]
        wd = re.sub(r'\(.*\)', '', wd)
    wNotes = [wn.rstrip(' )') for wn in rStr[wEnd:].split('(')[1:]]
    wordDf[wd] = {
        'alternatives': altern,
        'notes':        wNotes[1:],
        'partOfSpeech': wNotes[0],
        'example':      [],
    }
    return wd


def addExample(wordDf, wd, example):
    wordDf[wd]['example'].append(example)
    # print(f"'{wd}' example: {wordDf[wd]['example']}")


def fileTowordDf(wordFile, wordDf):
    with open(wordFile) as f:
        curWd = None
        for line in f:
            if line.isspace():  # ignore empty line
                continue
            line = line.strip()
            if line.isupper() and len(line) == 1:
                print(f"ignore '{line}'\n")
                continue
            # new word or example
            if line.startswith('.'):  # example of current word
                if not curWd:
                    continue  # no current word
                addExample(wordDf, curWd, line.lstrip(" ."))
            else:
                curWd = addWord(wordDf, line)


def dbQueryWord(cur, wd):
    sql = f'select phonetic, definition, translation from ecdict where word = "{wd}"'
    cur.execute(sql)
    return cur.fetchone()


def lookupDict(wordDf, dbFile):
    maxlen = 640
    # ecdict imported from https://github.com/skywind3000/ECDICT
    conn = sqlite3.connect(dbFile)
    cur = conn.cursor()
    invalidWords = []
    # lookup the ecdict
    for wd in wordDf:
        lwd = wd.strip('.!?')
        row = dbQueryWord(cur, lwd)
        if row:
            wordDf[wd]['phonetic'], wordDf[wd]['definition'], wordDf[wd]['translation'] = row
        else:  # try lookup lower case word
            row = dbQueryWord(cur, lwd.lower())
            if row:
                wordDf[wd]['phonetic'], wordDf[wd]['definition'], wordDf[wd]['translation'] = row
            else:
                print(f"{wd} not found in ecdict, deleting")
                invalidWords.append(wd)
                continue
        deflen = len(wordDf[wd]['definition']) + len(wordDf[wd]['translation'])
        if deflen > maxlen:
            print(f'!!def of {wd} is {deflen}!!')
    conn.close()
    return invalidWords


def wordListToHtml(wordList, wordDf, ignoreWords, htmlFile, title):
    jscript = '''
        <script type="text/javascript">
            function flipBack(card)
            {
                var back = card.children[1]
                if (back.style.display != 'none') {
                    back.style.display = 'none';
                } else {
                    back.style.display = 'flex';
                }
            }
        </script>
    '''
    htmlHead = f'''
    <meta charset="UTF-8">
        <title>{title}</title>
        <link href="flashcard.css" rel="stylesheet">
        {jscript}
        </head>
        <body>
    '''
    htmlTail = '''
    </body></html>
    '''
    outFile = open(htmlFile, 'w', encoding='utf-8')
    outFile.write(htmlHead)
    # format word list to html
    for wd in wordList:
        if wd not in wordDf:
            print(f"{wd} not found, ignore!")
            continue
        partOfSpeech = ''
        if wordDf[wd]['partOfSpeech']:
            partOfSpeech += f"({wordDf[wd]['partOfSpeech']})"
        alternatives = ''
        if wordDf[wd]['alternatives']:
            alternatives = '/' + '/'.join(wordDf[wd]['alternatives'])
        phonetic = ''
        if wordDf[wd]['phonetic']:
            phonetic = f"[{wordDf[wd]['phonetic']}]"
        notes = ''
        if wordDf[wd]['notes']:
            notes = '<p>' + '; '.join(wordDf[wd]['notes']) + '</p>'
        examples = ''
        if wordDf[wd]['example']:
            for item in wordDf[wd]['example']:
                examples += f"<li>{item}</li>\n"
        if examples:
            examples = '<ul>' + examples + '</ul>'
        translation = wordDf[wd]['translation'].replace('\\n', '<br>\n')
        if translation:
            translation = '<p>' + translation + '</p>'
        definition = ''
        if wd not in ignoreWords:
            definition = wordDf[wd]['definition'].replace('\\n', '<br>\n')
        if definition:
            definition = '<p>' + definition + '</p>'

        cardHtml = f'''
        <div class="container">
            <div class="watermark">Charlie</div>
            <div class="card" onclick="flipBack(this)">
                <div class="front">
                    <h1>
                        {wd}{alternatives}
                        <span class="sideword"> {partOfSpeech}</span><br>
                        {phonetic}
                    </h1>
                    {notes}
                    {examples}
                </div>
                <div class="back">
                    {translation}
                    {definition}
                </div>
            </div>
        </div>
        '''
        outFile.write(cardHtml)

    outFile.write(htmlTail)
    outFile.close()


def printWrodDfStats(wordDf):
    # general info of wordDf
    words = wordDf.keys()
    for c in string.ascii_lowercase:
        cnt = len([wd for wd in words if wd[0].lower() == c])
        print(f'{c}: {cnt}')
    print("total words: " + str(len(words)))


def woriteWordDfHtmlByGrp(wordDf, workDir, ignoreWords):
    htmlOutGrp = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'ijk', 
                  'l', 'm', 'no', 'p', 'qr', 's', 't', 'uvwxyz']
    for i in htmlOutGrp:
        htmlFile = f'{workDir}words_{i}.html'
        wlist = [wd for wd in wordDf if (wd[0].lower() in i)]
        wordListToHtml(wlist, wordDf, ignoreWords, htmlFile, f'Cambridge English A2 vocabulary Flashcards {i}')


if __name__ == "__main__":
    workDir = 'd:\\data\\flashcards\\'
    wordDf = {}
    readWordsFromJson = True
    wdJsnFile = workDir + 'a2keyWL.json'
    if readWordsFromJson:
        wordDf = json.load(open(wdJsnFile, 'r', encoding='utf-8'))
    else:
        wordFile = workDir + 'a2vl.txt'
        fileTowordDf(wordFile, wordDf)
        dbFile = workDir + 'ecdict.db'
        invalidWords = lookupDict(wordDf, dbFile)
        for wd in invalidWords:
            del wordDf[wd]
        # write wordDf to json file
        with open(wdJsnFile, 'w', encoding='utf-8') as jf:
            json.dump(wordDf, jf, sort_keys=True)
    #printWrodDfStats(wordDf)
    # words with loong definitions
    # wlist = ['CD', 'against', 'answer', 'as', 'capital', 'chips', 'colour', 'directions', 'father', 'field',
    #          'for', 'from', 'gas', 'glasses', 'green', 'herself', 'himself', 'hot', 'into', 'kite', 'left', 
    #          'long', 'lots', 'more', 'mouse', 'mushroom', 'of', 'post', 'programme', 'right', 'shall', 'shorts',
    #          'since', 'stripes', 'studies', 'subject', 'than', 'that', 'to', 'us', 'water', 'what', 'when', 'where',
    #          'which', 'with', 'without', 'work']
    # wordListToHtml(wlist, wordDf, [], f'{workDir}test.html', 'flashcard test')

    # write all words in one large html file
    wordListToHtml(wordDf.keys(), wordDf, [], f'{workDir}test.html', 'Cambridge English A2 vocabulary Flashcards')

    # grp = 'uvwxyz'
    # wlist = [wd for wd in wordDf if (wd[0].lower() in grp)]
    # wordListToHtml(wlist, wordDf, [], f'{workDir}words_{grp}.html', f'Cambridge English A2 vocabulary Flashcards {grp}')

    # write html group by leading letter
    # woriteWordDfHtmlByGrp(wordDf, workDir, [])
