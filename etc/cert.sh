#!/bin/bash

key_path=""
cert_path=""
domain=""

while getopts ":d:k:c:" option; do
   case $option in
      d) # Enter a domain name
         domain=$OPTARG;;
      k) # Specify key path
         key_path=$OPTARG;;
      c) # Specify certificate path
         cert_path=$OPTARG;;
   esac
done

# Check if the domain, key path, and certificate path are provided
if [ -z "$domain" ]; then
    echo "Domain is required."
    exit 1
fi

if [ -z "$key_path" ] || [ -z "$cert_path" ]; then
    echo "Both key and certificate paths must be provided."
    exit 1
fi

# Creating wildcard certificate
wildcard_domain="*.$domain"

openssl req -x509 -newkey rsa:2048 -sha256 -days 36500 -nodes \
  -keyout "$key_path" -out "$cert_path" -extensions ext  -config \
  <(echo "[req]"; 
    echo distinguished_name=req; 
    echo "[ext]";
    echo "keyUsage=critical,digitalSignature,keyEncipherment";
    echo "extendedKeyUsage=serverAuth";
    echo "basicConstraints=critical,CA:FALSE";
    echo "subjectAltName=DNS:$wildcard_domain,DNS:$domain";
    ) -subj "/CN=varo"
