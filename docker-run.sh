#!/bin/bash
docker-compose scale beanstalk=3 && \
/usr/local/bin/docker run -it \
    -v `pwd`:/var/www \
    -w /var/www \
    --rm=true \
    --link beanstalk_beanstalk_1 \
    --link beanstalk_beanstalk_2 \
    --link beanstalk_beanstalk_3 \
    php:7.2-cli /bin/bash
