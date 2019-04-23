for i in `cat node.cfg` 
do
WORKER_HOST=`echo ${i} | awk -F : '{print $1}'`
WORKER_IP=`echo ${i} | awk -F : '{print $2}'` 
cp node-csr.json ${WORKER_HOST}-csr.json
sed -i "s/WORKER_HOST/${WORKER_HOST}/g"  ${WORKER_HOST}-csr.json
cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -hostname=${WORKER_HOST},${WORKER_IP} -profile=kubernetes ${WORKER_HOST}-csr.json | cfssljson -bare ${WORKER_HOST}
done
