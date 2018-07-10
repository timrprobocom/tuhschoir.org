#! /bin/sh

for i in *.jpg *.png *.gif
do 
    convert "$i" -resize 100x100^ -gravity center -extent 100x100 "thumbs/thumb_$i"
done
