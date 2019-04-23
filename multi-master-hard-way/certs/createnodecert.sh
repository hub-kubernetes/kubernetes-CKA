for i in `cat node.cfg` 
do
WORKER_HOST=`echo ${i} | awk -F : '{print $1}'`
WORKER_IP=`echo ${i} | awk -F : '{print $2}'` 
cp node-csr.json ${WORKER_HOST}-csr.json
sed -i "s/WORKER_HOST/${WORKER_HOST}/g"  ${WORKER_HOST}-csr.json
echo $WORKER_HOST
echo $WORKER_IP
done
