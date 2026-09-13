#!/bin/bash
set -euo pipefail

REGION="${AWS_DEFAULT_REGION:-us-east-1}"
BUCKET="${AWS_BUCKET:-biblioteca-capas}"

awslocal s3api create-bucket \
    --bucket "$BUCKET" \
    --region "$REGION" >/dev/null

awslocal s3api put-bucket-cors \
    --bucket "$BUCKET" \
    --cors-configuration '{"CORSRules":[{"AllowedMethods":["GET","PUT"],"AllowedOrigins":["*"],"AllowedHeaders":["*"]}]}'
