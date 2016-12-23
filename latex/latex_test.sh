#!/bin/bash
clear
Bk_details=$1
Contrib_details=$2
Data_all=$3
Dep_dat=$4

CURDIR=$PWD;

if [ -a /$CURDIR/TEX ]
then 
rm TEX
fi

if [ -a /$CURDIR/TEX_dep ]
then 
rm TEX_dep
fi

if [ -a /$CURDIR/TEX_new ]
then 
rm TEX_new
fi


IFS_old="$IFS"
IFS=# read col1 col2 col3 col4 col5 col6 col7 col8 < $Contrib_details;
IFS=# read colB1 colB2 colB3 colB4 colB5 colB6 colB7 < $Bk_details;
col1=${col1/&/\\&};
col2=${col2/&/\\&};
col3=${col3/&/\\&};
col4=${col4/&/\\&};
col5=${col5/&/\\&};
col6=${col6/&/\\&};
col7=${col7/&/\\&};
col8=${col8/&/\\&};
colB1=${colB1/&/\\&};
colB2=${colB2/&/\\&};
colB3=${colB3/&/\\&};
colB4=${colB4/&/\\&};
colB5=${colB5/&/\\&};
colB6=${colB6/&/\\&};
colB7=${colB7/&/\\&};
echo \\title\{Scilab Textbook Companion \for "\\\\"$colB1"\\\\"by $colB2"\\footnote{Funded by a grant from the National Mission on Education through ICT, http://spoken-tutorial.org/NMEICT-Intro. This Textbook Companion and Scilab codes written in it can be downloaded from the \"Textbook Companion Project\" section at the website http://scilab.in}}" >>$CURDIR/TEX

echo \\author\{ Created by \\\\$col1\\\\$col2\\\\$col3\\\\$col4\\\\ College Teacher\\\\$col5\\\\Cross\-Checked by \\\\$col6\\\\$col8}>>$CURDIR/TEX

IFS="$IFS_old"

echo \\date{\\today}>>$CURDIR/TEX
echo \\begin{document}>>$CURDIR/TEX
echo \\maketitle >>$CURDIR/TEX

echo >>$CURDIR/TEX
echo \\chapter*{Book Description}>>$CURDIR/TEX
echo \\begin{description}>>$CURDIR/TEX
echo  \\item [Title:] ${colB1}>>$CURDIR/TEX
echo  \\item [Author:] ${colB2}>>$CURDIR/TEX 
echo  \\item [Publisher:] ${colB4}>>$CURDIR/TEX
echo  \\item [Edition:] ${colB5}>>$CURDIR/TEX
echo  \\item [Year:] ${colB6}>>$CURDIR/TEX
echo  \\item [ISBN:] ${colB3}>>$CURDIR/TEX
echo \\end{description}>>$CURDIR/TEX
echo >> $CURDIR/TEX

echo \\newpage >> $CURDIR/TEX
echo \\vspace*{3cm}>>$CURDIR/TEX

echo Scilab numbering policy used in this document and the relation to the above book.>>$CURDIR/TEX
echo \\begin{description}>>$CURDIR/TEX 
echo \\item[Exa]  Example \(Solved example\)>>$CURDIR/TEX 
echo \\item[Eqn]  Equation \(Particular equation of the above book\)>>$CURDIR/TEX
echo \\item[AP]   Appendix to Example\(Scilab Code that is an Appednix to a particular Example of the above book\)>>$CURDIR/TEX
echo \\end{description}>>$CURDIR/TEX
echo "For example, Exa~3.51 means solved example 3.51 of this book. Sec~2.3 means a scilab code whose theory is explained in Section 2.3 of the book.">>$CURDIR/TEX
echo>>$CURDIR/TEX

echo \\tableofcontents >>$CURDIR/TEX
echo \\listofcode >>$CURDIR/TEX
	if  grep -c ".jpg\|.JPEG\|.png\|.jpeg\|.JPG" $Data_all
	then
		echo \\listoffigures >>$CURDIR/TEX
	fi
echo>> $CURDIR/TEX
j=0;
k=1;
#sort -t '#' -k 3,3 -k 1,1 -g $Data_all > database_sort
sort -t '.' -k 1,1n -k 2,2n -k 3,3n  -k 4,4n $Data_all > database_sort

while IFS=# read col1 col2 col3 col4 col5 col6 col7 col8 col9; do
col2=${col2/&/\\&};
col3=${col3/&/\\&};
col4=${col4/&/\\&};
col8=${col8/&/\\&};
chap_diff=$(($col1 - $j))
if [ $chap_diff -eq 1 ]; then
	echo \\chapter{$col2}>>$CURDIR/TEX
	echo >>$CURDIR/TEX
fi

if [ $chap_diff -gt 1 ]; then
        echo >>$CURDIR/TEX
	echo \\setcounter{chapter}{$(($col1-1))}>>$CURDIR/TEX
	echo \\chapter{$col2}>>$CURDIR/TEX
	echo >>$CURDIR/TEX

fi

if [ $col7 != D ]
then
echo \\vspace*{10mm}>>$CURDIR/TEX
fi

  
if [ $col7 = S ]
then
 echo \\curlable{Exa~$col3} >> $CURDIR/TEX
 echo \\begin{code} >> $CURDIR/TEX
 echo \\tcaption{$col4}{$col4} >> $CURDIR/TEX
 echo \\lstinputlisting{../$col6}  >> $CURDIR/TEX
 echo \\end{code} >> $CURDIR/TEX
 echo >>$CURDIR/TEX
fi

if [ $col7 =  D ]
then
#echo check Appendix \\ref{AP:$col9} for dependency \$$col5\$ >> $CURDIR/TEX
echo check Appendix \\ref{AP:$col9} for dependency:  {\\begin{alltt} \\hspace{2mm}  $col5 \\end{alltt}} >> $CURDIR/TEX
echo  >> $CURDIR/TEX
fi


if [ $col7 = X ]
then
echo This code can be downloaded from the website wwww.scilab.in >> $CURDIR/TEX
fi

if [ $col7 =  R  ]
then 
echo $col6 > $CURDIR/Figure_files
	if  grep -c ".jpg\|.JPEG\|.png\|.jpeg\|.JPG" $CURDIR/Figure_files 
	then
		 echo \\curlable{Fig~$col3} >> $CURDIR/TEX
		 echo \\begin{figure} >> $CURDIR/TEX
		 echo \\includegraphics[width=5in]{../$col6}  >> $CURDIR/TEX
		 echo \\caption{$col4} >> $CURDIR/TEX
		 echo \\end{figure} >> $CURDIR/TEX
		 echo >> $CURDIR/TEX
	fi

fi

j=$col1
done < database_sort
rm Figure_files


if [  -s $Dep_dat ]
then

i=1;
echo \\chapter*{Appendix} >>$CURDIR/TEX

while IFS=# read col1 col2 col3 col4; do
col3=${col3/&/\\&};
echo \\curlable{AP~$i} >> $CURDIR/TEX;
echo \\begin{code} >> $CURDIR/TEX;
echo \\label{AP:$col4} >> $CURDIR/TEX
echo \\tcaption {$col3}{$col3} >> $CURDIR/TEX
echo \\lstinputlisting{../$col2}  >> $CURDIR/TEX
echo \\end{code} >> $CURDIR/TEX
echo >> $CURDIR/TEX
let "i+=1"
done < $Dep_dat

fi

cat Initial_body  TEX  > TEX_final.tex
echo \\end{document} >> $CURDIR/TEX_final.tex
clear
