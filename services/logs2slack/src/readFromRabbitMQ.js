// @flow

const { logger } = require('@amazeeio/lagoon-commons/src/local-logging');

const { getSlackinfoForProject } = require('@amazeeio/lagoon-commons/src/api');

var IncomingWebhook = require('@slack/client').IncomingWebhook;

export type ChannelWrapper = {
  ack: (msg: Object) => void,
}

export type RabbitMQMsg = {
  content: Buffer,
  fields: Object,
  properties: Object,
};

export type Project = {
  slack: Object,
  name: string,
};

async function readFromRabbitMQ (msg: RabbitMQMsg, channelWrapperLogs: ChannelWrapper): Promise<void> {
  const {
    content,
    fields,
    properties,
  } = msg;

  const logMessage = JSON.parse(content.toString())

  const {
    severity,
    project,
    uuid,
    event,
    meta,
    message
  } = logMessage

  const appId = msg.properties.appId || ""

 logger.verbose(`received ${event}`, logMessage)

  switch (event) {

    case "github:pull_request:closed:handled":
    case "github:pull_request:opened:handled":
    case "github:pull_request:synchronize:handled":
    case "github:delete:handled":
    case "github:push:handled":
    case "bitbucket:repo:push:handled":
    case "gitlab:push:handled":
    case "rest:deploy:receive":
    case "rest:remove:receive":
      sendToSlack(project, message, '#E8E8E8', ':information_source:', channelWrapperLogs, msg, appId)
      break;

    case "task:deploy-openshift:finished":
    case "task:remove-openshift:finished":
    case "task:remove-openshift-resources:finished":
    case "task:builddeploy-openshift:complete":
      sendToSlack(project, message, 'good', ':white_check_mark:', channelWrapperLogs, msg, appId)
      break;

    case "task:deploy-openshift:retry":
    case "task:remove-openshift:retry":
    case "task:remove-openshift-resources:retry":
      sendToSlack(project, message, 'warning', ':warning:', channelWrapperLogs, msg, appId)
      break;

    case "task:deploy-openshift:error":
    case "task:remove-openshift:error":
    case "task:remove-openshift-resources:error":
    case "task:builddeploy-openshift:failed":
      sendToSlack(project, message, 'danger', ':bangbang:', channelWrapperLogs, msg, appId)
      break;

    case "unresolvedProject:webhooks2tasks":
    case "unhandledWebhook":
    case "webhooks:receive":
    case "task:deploy-openshift:start":
    case "task:remove-openshift:start":
    case "task:remove-openshift-resources:start":
    case "task:builddeploy-openshift:running":
      // known logs entries that should never go to slack
      channelWrapperLogs.ack(msg)
      break;

    default:
      logger.warn(`unhandled log message ${event} ${JSON.stringify(logMessage)}`)
      return channelWrapperLogs.ack(msg)
  }

}

const sendToSlack = async (project, message, color, emoji, channelWrapperLogs, msg, appId) => {

  let projectSlack;
  try {
    projectSlack = await getSlackinfoForProject(project)
  }
  catch (error) {
    logger.error(`No Slack information found, error: ${error}`)
    return channelWrapperLogs.ack(msg)
  }

  await new IncomingWebhook(projectSlack.slack.webhook, {
    channel: projectSlack.slack.channel,
  }).send({
    attachments: [{
      text: `${emoji} ${message}`,
      color: color,
      "mrkdwn_in": ["pretext", "text", "fields"],
      footer: appId
    }]
  });
  channelWrapperLogs.ack(msg)
  return
}

module.exports = readFromRabbitMQ;
