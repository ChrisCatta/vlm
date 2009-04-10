#!/bin/bash
##=================================================================
##   DOIT ETRE APPELE TOUTES LES MINUTES A COMPTER DE V0.9.8     ##
##=================================================================
#VLMRACINE=/base/de/vlm #A configurer normalement fans le crontab
source $VLMRACINE/conf/conf_script || exit 1

LOG=$VLMLOG/$(date +%Y%m%d_%H%M)-$1-cronvlm.log
export LOGFILE_MAX_AGE=7

# Si on trouve un lock, on s'intéresse à son age
# Si sa date de dernière modif a plus de 60 secondes, c'est pas normal.
if [ -f $VLMTEMP/cronvlm.$1.lock ] ; then
   Son_Age=$(stat -c "%Y" $VLMTEMP/cronvlm.$1.lock )
   (( Son_Age += 60 ))
   if [ $Son_age  -gt $(date +%s) ] ; then
      
      echo "=== LOCK FOUND : killing old engine instance" >> $LOG
      kill -SIGQUIT $(cat $VLMTEMP/cronvlm.$1.lock )

   fi
fi

rm -f $VLMTEMP/cronvlm.$1.lock
echo $$ > $VLMTEMP/cronvlm.$1.lock

cd $VLMJEUROOT/moteur
echo -e "\n" >> $LOG
echo  "******************* starting the engine ********************" >> $LOG
date >> $LOG
echo "************************************************************" >> $LOG
nice -10 $VLMPHPPATH moteur.php $* >> $LOG 2>&1

# Voir Option MAIL_FOR_COASTCROSSING dans conf_script
[ "$MAIL_FOR_COASTCROSSING" == true ] && grep CROSSED $LOG | sed 's/.*player //g' | sed 's/ CROSSED.*$//g' | while read idusers ; do
        sed -n "/user $idusers:/,/DONE/p"  $LOG >$VLMTEMP/CC-$idusers.log
        mail -s "COAST CROSSING : BOAT $idusers" vlm@virtual-winds.com -- -F "VLM-ENGINE" -f "vlm@virtual-winds.com" <$VLMTEMP/CC-$idusers.log
        rm -f $VLMTEMP/CC-$idusers.log
done

rm -f $VLMTEMP/cronvlm.$1.lock
gzip -9 $LOG

# Purge des anciens logs
#===8<===
cd $VLMLOG
[ $(pwd) == "$VLMLOG" ] && find . -name "*--cronvlm.log.gz" -mtime +$LOGFILE_MAX_AGE -exec rm -f {} \;
#===8<===

