#!/bin/bash

oc rollout --insecure-skip-tls-verify -n ${OPENSHIFT_PROJECT} status dc/${SERVICE_NAME} --watch