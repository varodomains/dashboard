'use strict';
const {Address, Network} = require('hsd');
const network = Network.get('main');

const version = parseInt(process.argv[2]);
const hash = process.argv[3];

const data = Buffer.from(hash, 'hex');
const addr = Address.fromProgram(version, data);
console.log(addr.toString(network));