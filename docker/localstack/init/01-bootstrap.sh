#!/bin/bash
set -euo pipefail

REGION="${AWS_DEFAULT_REGION:-us-east-1}"
QUEUES=("default" "notifications")

for queue in "${QUEUES[@]}"; do
    awslocal sqs create-queue \
        --queue-name "${queue}-failed" \
        --region "$REGION" >/dev/null

    dlq_arn=$(awslocal sqs get-queue-attributes \
        --queue-url "http://localhost:4566/000000000000/${queue}-failed" \
        --attribute-names QueueArn \
        --region "$REGION" \
        --query 'Attributes.QueueArn' --output text)

    awslocal sqs create-queue \
        --queue-name "$queue" \
        --region "$REGION" \
        --attributes "{\"VisibilityTimeout\":\"90\",\"RedrivePolicy\":\"{\\\"deadLetterTargetArn\\\":\\\"${dlq_arn}\\\",\\\"maxReceiveCount\\\":\\\"3\\\"}\"}" >/dev/null

done
